<?php
$orderJson = json_encode($order ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$reviewsJson = json_encode($reviews ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$disputeJson = json_encode($dispute ?? null, JSON_HEX_APOS | JSON_HEX_TAG);
$isBuyerVal = $isBuyer ? 'true' : 'false';
$isSellerVal = $isSeller ? 'true' : 'false';
?>

<div class="space-y-6" x-data="orderDetail">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-dark-400">
        <a href="/orders" class="hover:text-gold-400 transition"><?= t('orders.title', 'Orders') ?></a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-white"><?= t('orders.order', 'Order') ?> #<span x-text="order.id"></span></span>
    </nav>

    <!-- Order Header -->
    <div class="glass rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-display font-bold text-white flex items-center gap-3">
                    <?= t('orders.order', 'Order') ?> #<span x-text="order.id"></span>
                    <span class="status-badge" :class="'status-' + order.escrow_status" x-text="order.escrow_status"></span>
                </h1>
                <p class="text-sm text-dark-400 mt-1" x-text="'Placed ' + formatDate(order.created_at)"></p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-display font-bold text-gold-400" x-text="'$' + parseFloat(order.total_paid).toFixed(2)"></p>
            </div>
        </div>
    </div>

    <!-- Escrow Timeline -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-5"><?= t('orders.escrow_status', 'Escrow Status') ?></h2>
        <div class="escrow-timeline">
            <template x-for="(step, idx) in escrowSteps" :key="step.key">
                <div class="escrow-step">
                    <div class="step-line" :class="step.lineClass"></div>
                    <div class="step-dot" :class="step.dotClass" x-text="idx + 1"></div>
                    <p class="text-xs text-center mt-2 font-medium" :class="step.active ? 'text-gold-400' : step.completed ? 'text-green-400' : 'text-dark-500'" x-text="step.label"></p>
                    <p class="text-[10px] text-dark-500 text-center mt-0.5" x-text="step.date || ''"></p>
                </div>
            </template>
        </div>
        <div x-show="order.auto_complete_at && order.escrow_status === 'shipped'" class="mt-4 p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg">
            <p class="text-xs text-blue-400 flex items-center gap-2">
                <i data-lucide="clock" class="w-4 h-4"></i>
                <?= t('orders.auto_complete', 'Auto-completes in') ?> <span class="font-bold" x-text="autoCompleteCountdown"></span>
            </p>
        </div>
    </div>

    <!-- Card Info -->
    <div class="glass rounded-2xl p-6">
        <div class="flex items-center gap-5">
            <img :src="cardImgSrc(order.card_image_url)" :data-ext-src="order.card_image_url" class="w-20 h-28 rounded-xl object-cover bg-dark-700 flex-shrink-0" onerror="cardImgErr(this)">
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold text-white" x-text="order.card_name"></h3>
                <p class="text-sm text-dark-400" x-text="order.card_set_id"></p>
                <div class="flex items-center gap-2 mt-2">
                    <template x-if="order.condition">
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full" :class="conditionClass(order.condition)" x-text="order.condition"></span>
                    </template>
                    <span class="text-xs text-dark-400" x-text="'Qty: ' + (order.quantity || 1)"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Price Breakdown -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('orders.price_breakdown', 'Price Breakdown') ?></h2>
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-dark-400"><?= t('marketplace.item_price', 'Item Price') ?></span>
                <span class="text-white" x-text="'$' + parseFloat(order.item_price).toFixed(2)"></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-dark-400"><?= t('marketplace.shipping', 'Shipping') ?></span>
                <span class="text-white" x-text="order.shipping_cost > 0 ? '$' + parseFloat(order.shipping_cost).toFixed(2) : 'Free'"></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-dark-400"><?= t('orders.fees', 'Fees') ?></span>
                <span class="text-white" x-text="'$' + parseFloat(order.fee_amount || 0).toFixed(2)"></span>
            </div>
            <div class="flex justify-between text-sm font-bold border-t border-dark-600 pt-2">
                <span class="text-white"><?= t('marketplace.total', 'Total') ?></span>
                <span class="text-gold-400" x-text="'$' + parseFloat(order.total_paid).toFixed(2)"></span>
            </div>
        </div>
    </div>

    <!-- Shipping & Tracking -->
    <div class="glass rounded-2xl p-6" x-show="order.tracking_number || order.escrow_status === 'shipped'">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('orders.shipping_info', 'Shipping Information') ?></h2>
        <div class="space-y-2">
            <div class="flex justify-between text-sm" x-show="order.shipping_carrier">
                <span class="text-dark-400"><?= t('orders.carrier', 'Carrier') ?></span>
                <span class="text-white" x-text="order.shipping_carrier"></span>
            </div>
            <div class="flex justify-between text-sm" x-show="order.tracking_number">
                <span class="text-dark-400"><?= t('orders.tracking', 'Tracking Number') ?></span>
                <span class="text-white font-mono text-xs" x-text="order.tracking_number"></span>
            </div>
            <div class="flex justify-between text-sm" x-show="order.shipped_at">
                <span class="text-dark-400"><?= t('orders.shipped_date', 'Shipped Date') ?></span>
                <span class="text-white" x-text="formatDate(order.shipped_at)"></span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="glass rounded-2xl p-6 space-y-4">
        <!-- Seller: Mark Shipped -->
        <div x-show="isSeller && order.escrow_status === 'paid'">
            <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('orders.ship_order', 'Ship This Order') ?></h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1"><?= t('orders.carrier', 'Carrier') ?></label>
                    <input type="text" x-model="shippingCarrier" placeholder="<?= htmlspecialchars(t('orders.carrier_placeholder', 'e.g. USPS, UPS, FedEx')) ?>"
                        class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1"><?= t('orders.tracking', 'Tracking Number') ?></label>
                    <input type="text" x-model="trackingNumber" placeholder="<?= htmlspecialchars(t('orders.tracking_placeholder', 'Tracking number')) ?>"
                        class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                </div>
            </div>
            <button @click="markShipped()" :disabled="actionLoading"
                class="w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50 flex items-center justify-center gap-2">
                <i data-lucide="truck" class="w-5 h-5"></i>
                <span x-show="!actionLoading"><?= t('orders.mark_shipped', 'Mark as Shipped') ?></span>
                <span x-show="actionLoading"><?= t('orders.processing', 'Processing...') ?></span>
            </button>
        </div>

        <!-- Buyer: Confirm Receipt & Dispute -->
        <div x-show="isBuyer && order.escrow_status === 'shipped'" class="space-y-3">
            <button @click="confirmDelivery()" :disabled="actionLoading"
                class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg text-sm font-bold hover:from-green-400 hover:to-emerald-500 transition disabled:opacity-50 flex items-center justify-center gap-2" style="color:#fff !important">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <span x-show="!actionLoading"><?= t('orders.confirm_receipt', 'Confirm Receipt') ?></span>
                <span x-show="actionLoading"><?= t('orders.processing', 'Processing...') ?></span>
            </button>
            <button @click="disputeModalOpen = true"
                class="w-full py-3 glass rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/5 transition flex items-center justify-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i> <?= t('orders.open_dispute', 'Open Dispute') ?>
            </button>
        </div>
    </div>

    <!-- Review Section -->
    <div class="glass rounded-2xl p-6" x-show="order.escrow_status === 'completed'">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('orders.reviews', 'Reviews') ?></h2>

        <!-- Existing Reviews -->
        <div class="space-y-4" x-show="reviews.length > 0">
            <template x-for="review in reviews" :key="review.id">
                <div class="p-4 bg-dark-800/30 rounded-xl">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-full bg-dark-700 flex items-center justify-center">
                            <span class="text-xs font-bold text-dark-300" x-text="(review.reviewer_username || '?').charAt(0).toUpperCase()"></span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white font-medium" x-text="review.reviewer_username"></p>
                            <div class="rating-stars">
                                <template x-for="i in 5" :key="i">
                                    <i data-lucide="star" class="w-3 h-3 star" :class="i <= review.rating ? 'filled' : ''"></i>
                                </template>
                            </div>
                        </div>
                        <span class="text-xs text-dark-400" x-text="formatDate(review.created_at)"></span>
                    </div>
                    <p class="text-sm text-dark-300" x-text="review.comment"></p>
                </div>
            </template>
        </div>

        <!-- Review Form -->
        <div x-show="!hasReviewed" class="mt-4">
            <h3 class="text-sm text-white font-medium mb-3"><?= t('orders.leave_review', 'Leave a Review') ?></h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('orders.rating', 'Rating') ?></label>
                    <div class="flex gap-1">
                        <template x-for="i in 5" :key="i">
                            <button @click="reviewRating = i" class="p-1 transition hover:scale-110">
                                <i data-lucide="star" class="w-6 h-6" :class="i <= reviewRating ? 'text-yellow-400 fill-current' : 'text-dark-500'"></i>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('orders.comment', 'Comment') ?></label>
                    <textarea x-model="reviewComment" rows="3" placeholder="<?= htmlspecialchars(t('orders.review_placeholder', 'Share your experience...')) ?>"
                        class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition resize-none"></textarea>
                </div>
                <button @click="submitReview()" :disabled="reviewSubmitting || reviewRating === 0"
                    class="px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50">
                    <span x-show="!reviewSubmitting"><?= t('orders.submit_review', 'Submit Review') ?></span>
                    <span x-show="reviewSubmitting"><?= t('orders.submitting', 'Submitting...') ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Dispute Section -->
    <div class="glass rounded-2xl p-6 border border-red-500/20" x-show="dispute">
        <h2 class="text-sm font-bold text-red-400 uppercase tracking-wider mb-3 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i> <?= t('orders.dispute', 'Dispute') ?>
        </h2>
        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-dark-400"><?= t('orders.dispute_reason', 'Reason') ?></span>
                <span class="text-white" x-text="dispute?.reason"></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-dark-400"><?= t('orders.dispute_status', 'Status') ?></span>
                <span class="status-badge status-disputed" x-text="dispute?.status"></span>
            </div>
            <div x-show="dispute?.description" class="text-sm text-dark-300 p-3 bg-dark-800/30 rounded-lg" x-text="dispute?.description"></div>
        </div>
    </div>

    <!-- Dispute Modal -->
    <div x-show="disputeModalOpen" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-dark-900/80 backdrop-blur-sm" @click.self="disputeModalOpen = false">
        <div class="glass rounded-2xl p-6 w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-display font-bold text-red-400 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i> <?= t('orders.open_dispute', 'Open Dispute') ?>
                </h3>
                <button @click="disputeModalOpen = false" class="text-dark-400 hover:text-white transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('orders.dispute_reason', 'Reason') ?></label>
                    <select x-model="disputeReason" class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                        <option value=""><?= t('orders.select_reason', 'Select a reason...') ?></option>
                        <option value="not_received"><?= t('orders.not_received', 'Item not received') ?></option>
                        <option value="wrong_item"><?= t('orders.wrong_item', 'Wrong item received') ?></option>
                        <option value="damaged"><?= t('orders.damaged', 'Item damaged') ?></option>
                        <option value="not_as_described"><?= t('orders.not_as_described', 'Not as described') ?></option>
                        <option value="other"><?= t('orders.other', 'Other') ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('orders.dispute_description', 'Description') ?></label>
                    <textarea x-model="disputeDescription" rows="4" placeholder="<?= htmlspecialchars(t('orders.dispute_placeholder', 'Describe the issue in detail...')) ?>"
                        class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition resize-none"></textarea>
                </div>
                <button @click="openDispute()" :disabled="disputeSubmitting || !disputeReason || !disputeDescription"
                    class="w-full py-3 bg-red-500 hover:bg-red-600 rounded-lg text-sm font-bold transition disabled:opacity-50 flex items-center justify-center gap-2" style="color:#fff !important">
                    <span x-show="!disputeSubmitting"><?= t('orders.submit_dispute', 'Submit Dispute') ?></span>
                    <span x-show="disputeSubmitting"><?= t('orders.submitting', 'Submitting...') ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.__PAGE_DATA = {
    order: <?= $orderJson ?>,
    reviews: <?= $reviewsJson ?>,
    dispute: <?= $disputeJson ?>,
    isBuyer: <?= $isBuyerVal ?>,
    isSeller: <?= $isSellerVal ?>
};
</script>
<script src="<?= asset_v('/assets/js/pages/orders.js') ?>"></script>
