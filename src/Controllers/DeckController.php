<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Deck;
use App\Models\Card;

class DeckController
{
    public function index(): void
    {
        Auth::requireAuth();

        $decks = Deck::getByUserId(Auth::id());

        View::render('pages/decks/index', [
            'title' => 'My Decks - One Piece TCG Deck Builder - MyOPCards',
            'seoDescription' => 'Manage your One Piece TCG decks. Build competitive decks with our deck builder, get card recommendations, and prepare for ranked matches.',
            'seoKeywords' => 'One Piece TCG deck builder, OPTCG decks, One Piece card game deck, build OPTCG deck, One Piece TCG deck list, OPTCG deck strategy',
            'seoRobots' => 'noindex, nofollow',
            'decks' => $decks,
            'sets' => Card::getDistinctValues('set_id'),
            'colors' => Card::getDistinctValues('card_color'),
            'types' => Card::getDistinctValues('card_type'),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();

        View::render('pages/decks/builder', [
            'title' => 'Create Deck - One Piece TCG Deck Builder - MyOPCards',
            'seoDescription' => 'Build a new One Piece TCG deck with our interactive deck builder. Choose your leader, add characters, events and stages. Get smart card recommendations.',
            'seoKeywords' => 'One Piece TCG deck builder, create OPTCG deck, OPTCG deck creator, build One Piece deck, OPTCG card selection',
            'seoRobots' => 'noindex, nofollow',
            'deck' => null,
            'sets' => Card::getDistinctValues('set_id'),
            'colors' => Card::getDistinctValues('card_color'),
            'types' => Card::getDistinctValues('card_type'),
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();

        $deck = Deck::getById($id, Auth::id());
        if (!$deck) {
            http_response_code(404);
            View::render('pages/404');
            return;
        }

        View::render('pages/decks/builder', [
            'title' => 'Edit Deck - ' . $deck['name'] . ' - MyOPCards',
            'seoRobots' => 'noindex, nofollow',
            'deck' => $deck,
            'sets' => Card::getDistinctValues('set_id'),
            'colors' => Card::getDistinctValues('card_color'),
            'types' => Card::getDistinctValues('card_type'),
        ]);
    }

    public function save(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode(['success' => false, 'error' => t('deck.invalid_request')]);
            return;
        }

        $result = Deck::save(Auth::id(), $payload);
        echo json_encode($result);
    }

    public function delete(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $id = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => t('deck.invalid_deck')]);
            return;
        }

        $ok = Deck::delete($id, Auth::id());
        echo json_encode(['success' => $ok]);
    }

    public function get(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $deck = Deck::getById($id, Auth::id());
        if (!$deck) {
            http_response_code(404);
            echo json_encode(['error' => t('deck.not_found')]);
            return;
        }

        echo json_encode($deck);
    }

    public function userDecks(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $decks = Deck::getByUserId(Auth::id());
        echo json_encode(['decks' => $decks]);
    }
}
