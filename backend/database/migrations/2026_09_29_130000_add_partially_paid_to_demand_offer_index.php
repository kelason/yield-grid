<?php

use App\Domain\Marketplace\Enums\DemandOfferStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS crop_demand_offers_one_active_per_farmer');

        $blocking = implode("','", DemandOfferStatus::blockingNewOffer());
        DB::statement(
            "CREATE UNIQUE INDEX crop_demand_offers_one_active_per_farmer ON crop_demand_offers (crop_demand_id, farmer_id) WHERE status IN ('{$blocking}')"
        );

        // Repair offers that were fully marked paid for a downpayment.
        DB::table('crop_demand_offers')
            ->where('status', DemandOfferStatus::PAID->value)
            ->whereIn('id', function ($query): void {
                $query->select('crop_demand_offer_id')->from('purchases')->where('is_downpayment', true);
            })
            ->update(['status' => DemandOfferStatus::PARTIALLY_PAID->value]);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS crop_demand_offers_one_active_per_farmer');

        $blocking = implode("','", [
            DemandOfferStatus::PENDING->value,
            DemandOfferStatus::ACCEPTED->value,
            DemandOfferStatus::PAID->value,
            DemandOfferStatus::DELIVERED->value,
        ]);
        DB::statement(
            "CREATE UNIQUE INDEX crop_demand_offers_one_active_per_farmer ON crop_demand_offers (crop_demand_id, farmer_id) WHERE status IN ('{$blocking}')"
        );
    }
};
