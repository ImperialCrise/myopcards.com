<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use PDO;

class WalletController
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();
        $userId = Auth::id();

        // Ensure wallet exists
        $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $wallet = $stmt->fetch();
        if (!$wallet) {
            $db->prepare(
                "INSERT INTO wallets (user_id, available_balance, reserved_balance, pending_balance, currency, created_at, updated_at)
                 VALUES (:uid, 0, 0, 0, 'USD', NOW(), NOW())"
            )->execute(['uid' => $userId]);
            $stmt->execute(['uid' => $userId]);
            $wallet = $stmt->fetch();
        }

        // Recent transactions
        $txStmt = $db->prepare(
            "SELECT * FROM wallet_transactions WHERE wallet_id = :wid ORDER BY created_at DESC LIMIT 20"
        );
        $txStmt->execute(['wid' => $wallet['id']]);
        $transactions = $txStmt->fetchAll();

        $stripeKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';

        View::render('pages/wallet/index', [
            'title' => 'My Wallet',
            'wallet' => $wallet,
            'transactions' => $transactions,
            'stripeKey' => $stripeKey,
        ]);
    }

    public function balance(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $db = Database::getConnection();
        $userId = Auth::id();

        $stmt = $db->prepare("SELECT available_balance, pending_balance, currency FROM wallets WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $wallet = $stmt->fetch();

        if (!$wallet) {
            echo json_encode(['success' => true, 'balance' => '0.00', 'pending_balance' => '0.00', 'currency' => 'USD']);
            return;
        }

        echo json_encode([
            'success' => true,
            'balance' => number_format((float)$wallet['available_balance'], 2, '.', ''),
            'pending_balance' => number_format((float)$wallet['pending_balance'], 2, '.', ''),
            'currency' => $wallet['currency'],
        ]);
    }

    public function createDeposit(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $amount = (float)($input['amount'] ?? 0);

        if ($amount < 10) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Minimum deposit amount is $10.00']);
            return;
        }

        if ($amount > 10000) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Maximum deposit amount is $10,000.00']);
            return;
        }

        try {
            $result = \App\Services\StripeService::createPaymentIntent(Auth::id(), $amount);
            echo json_encode([
                'success' => true,
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create payment: ' . $e->getMessage()]);
        }
    }

    public function confirmDeposit(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $paymentIntentId = trim($input['payment_intent_id'] ?? '');

        if (empty($paymentIntentId)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Missing payment intent ID']);
            return;
        }

        try {
            $confirmed = \App\Services\StripeService::confirmPaymentIntent($paymentIntentId, Auth::id());
            if (!$confirmed) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment could not be confirmed']);
                return;
            }

            $wallet = \App\Services\WalletService::deposit(
                Auth::id(),
                $confirmed['amount'],
                'deposit',
                'Deposit via Stripe',
                $paymentIntentId
            );

            echo json_encode([
                'success' => true,
                'balance' => number_format((float)$wallet['available_balance'], 2, '.', ''),
                'message' => 'Deposit successful',
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Deposit failed: ' . $e->getMessage()]);
        }
    }

    public function requestWithdrawal(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $amount = (float)($input['amount'] ?? 0);

        if ($amount < 20) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Minimum withdrawal amount is $20.00']);
            return;
        }

        $db = Database::getConnection();
        $userId = Auth::id();

        $stmt = $db->prepare("SELECT * FROM wallets WHERE user_id = :uid FOR UPDATE");
        $db->beginTransaction();

        try {
            $stmt->execute(['uid' => $userId]);
            $wallet = $stmt->fetch();

            if (!$wallet || (float)$wallet['available_balance'] < $amount) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
                return;
            }

            // Deduct from balance
            $db->prepare(
                "UPDATE wallets SET available_balance = available_balance - :amt, updated_at = NOW() WHERE id = :wid"
            )->execute(['amt' => $amount, 'wid' => $wallet['id']]);

            // Create pending withdrawal transaction
            $db->prepare(
                "INSERT INTO wallet_transactions (wallet_id, type, amount, balance_after, description, created_at)
                 VALUES (:wid, 'withdrawal', :amt, :bal, 'Withdrawal request', NOW())"
            )->execute([
                'wid' => $wallet['id'],
                'amt' => $amount,
                'bal' => (float)$wallet['available_balance'] - $amount,
            ]);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Withdrawal request submitted. It will be processed within 3-5 business days.',
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Withdrawal request failed']);
        }
    }

    public function transactions(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $db = Database::getConnection();
        $userId = Auth::id();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $wallet = $db->prepare("SELECT id FROM wallets WHERE user_id = :uid");
        $wallet->execute(['uid' => $userId]);
        $wallet = $wallet->fetch();

        if (!$wallet) {
            echo json_encode(['success' => true, 'transactions' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1]);
            return;
        }

        $total = (int)$db->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE wallet_id = :wid");
        $total->execute(['wid' => $wallet['id']]);
        $total = (int)$total->fetchColumn();

        $stmt = $db->prepare(
            "SELECT * FROM wallet_transactions WHERE wallet_id = :wid ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue('wid', $wallet['id'], PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function stripeWebhook(): void
    {
        header('Content-Type: application/json');

        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (empty($payload) || empty($sigHeader)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing payload or signature']);
            return;
        }

        try {
            $result = \App\Services\StripeService::handleWebhook($payload, $sigHeader);
            http_response_code(200);
            echo json_encode(['received' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Webhook handling failed: ' . $e->getMessage()]);
        }
    }
}
