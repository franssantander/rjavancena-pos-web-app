<?php

namespace Database\Seeders;

use App\Helper\Helper;
use App\Models\AuthModel;
use App\Models\HistoryModel;
use Faker\Factory as FakerFactory; // Add this at the top of your file
use Illuminate\Support\Str;
use App\Models\InventoryModel;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\InventoryProductModel;
use App\Models\NotificationModel;
use Illuminate\Support\Facades\Crypt;
use App\Models\UserPersonalAccessToken;
use App\Models\UserUserIdModel;

class DatabaseSeeder extends Seeder
{
    protected $helper, $fillable_attr_auths, $fillable_attr_inventorys, $fillable_attr_inventory_children, $fillable_attr_notification;

    public function __construct(
        Helper $helper,
        AuthModel $fillable_attr_auths,
        InventoryModel $fillable_attr_inventorys,
        InventoryProductModel $fillable_attr_inventory_children,
        NotificationModel $fillable_attr_notification
    ) {
        $this->helper = $helper;
        $this->fillable_attr_auths = $fillable_attr_auths;
        $this->fillable_attr_inventorys = $fillable_attr_inventorys;
        $this->fillable_attr_inventory_children = $fillable_attr_inventory_children;
        $this->fillable_attr_notification = $fillable_attr_notification;
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->userTblEmail();
            $this->inventoryParentTbl();
            $this->inventoryChildTbl();
            $this->notificationTbl();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Seeder error: ' . $e->getMessage());
        }
    }

    private function userTblEmail()
    {
        info('Starting userTblEmail seeder method');
        $ctr = 0;

        try {
            $items = [
                [
                    'user_id' => "18636745-ce34-4099-9442-f3216a62fa8c",
                    'email' => Crypt::encrypt('superadmin@superadmin.com'), // Encrypt email
                    'password' => Hash::make('superadmin@superadmin.com'),
                    'role' => 'SUPER_ADMIN',
                    'status' => 'ACTIVATE',
                    'verification_number' => $this->helper->faker6DigitNumber(),
                    'email_verified_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'user_id' => "6b7855ca-ec82-4fbc-a70c-c87d5be0401a",
                    'email' => Crypt::encrypt('admin@admin.com'), // Encrypt email
                    'password' => Hash::make('admin@admin.com'),
                    'role' => 'ADMIN',
                    'status' => 'ACTIVATE',
                    'verification_number' => $this->helper->faker6DigitNumber(),
                    'email_verified_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'user_id' => "5cbfef5f-0566-467f-95b1-8d980e6e6c8b",
                    'email' => Crypt::encrypt('cashier@cashier.com'), // Encrypt email
                    'password' => Hash::make('cashier@cashier.com'),
                    'role' => 'CASHIER',
                    'status' => 'ACTIVATE',
                    'verification_number' => $this->helper->faker6DigitNumber(),
                    'email_verified_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ];

            foreach ($items as $index => $item) {
                $created = AuthModel::create($item);

                // Determine the original un-hashed password for history logging
                $original_password = '';
                switch ($index) {
                    case 0:
                        $original_password = 'superadmin@superadmin.com';
                        break;
                    case 1:
                        $original_password = 'admin@admin.com';
                        break;
                    case 2:
                        $original_password = 'cashier@cashier.com';
                        break;
                }

                // Log the creation in HistoryModel
                HistoryModel::create([
                    'history_id' => 'history_id-' . ($created ? Str::uuid() : Str::uuid()), // Use $ctr as fallback if $created is false
                    'tbl_id' => $created ? $created->user_id : null,
                    'tbl_name' => 'users_tbl',
                    'column_name' => 'password',
                    'value' => Crypt::encrypt($original_password), // Encrypt the original password before storing it
                ]);

                // Log the creation in UserUserIdModel
                UserUserIdModel::create([
                    'user_id' => $created ? $created->user_id : null,
                ]);

                if (!$created) {
                    $ctr++;
                    throw new \Exception('Failed to store AuthModel');
                }
            }
        } catch (\Exception $e) {
            Log::error('Error creating AuthModel: ' . $e->getMessage());
            throw $e; // Re-throw the exception to bubble up to the run() method
        }

        info('Finished userTblEmail seeder method');
    }

    private function inventoryParentTbl()
    {
        info('Starting inventoryParentTbl seeder method');
        // Data to insert
        $items = [
            [
                'name' => "Acrylon",
                'category' => "Paint",
                'image' => null,
            ],
            [
                'name' => "Aluminum Ladder",
                'category' => "Tools",
                'image' => null,
            ],
        ];

        foreach ($items as $item) {
            // Prepare data for insertion
            $result_to_create = $this->helper->arrStoreMultipleData(
                $this->fillable_attr_inventorys->arrToStores(),
                $item,
                [],
                [],
                [],
            );

            // Create the InventoryModel instance with the selected attributes
            $created = InventoryModel::create($result_to_create);

            $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_inventorys->idToUpdate(), Str::uuid());
            if ($update_unique_id) {
                DB::rollBack();
                return $update_unique_id;
            }

            if (!$created) {
                $error_message = [
                    'message' => 'Failed to store inventory Parent',
                    'parameter' => $created,
                ];
                throw new \Exception(json_encode($error_message));
            }
        }

        info('Finished inventoryParentTbl seeder method');
    }

    private function inventoryChildTbl()
    {
        $faker = FakerFactory::create('en_US'); // Ensure English locale

        info('Starting inventoryChildTbl seeder method');
        $arr_to_store = [
            'inventory_id',
            'item_code',
            'image',
            'name',
            'category',
            'refundable',
            'supplier_name',
            'retail_price',
            'discounted_price',
            'unit_supplier_price',
            'stocks',
            'low_stocks',
            'moderate_stocks',
        ];

        $inventory1 = InventoryModel::find(1);
        $inventory2 = InventoryModel::find(2);

        $inventory1Enc = Crypt::encrypt($inventory1->inventory_id);
        $inventory2Enc = Crypt::encrypt($inventory2->inventory_id);

        $items = [];

        // Loop to create 150 items
        for ($i = 1; $i <= 500; $i++) {
            $inventory = $i <= 250 ? $inventory1 : $inventory2; // First 100 items from inventory1, rest from inventory2
            $inventoryEnc = $i <= 250 ? $inventory1Enc : $inventory2Enc;
            $name = $i <= 250 ? "Acrylon " . $i : "Aluminum Ladder " . $i;

            $items[] = [
                'inventory_id' => $inventoryEnc,
                'item_code' => $this->helper->faker12DigitNumber(),
                'image' => null, // Keeping image as null, adjust if needed
                'name' => $name, // Generate random names with item number
                'category' => $inventory->category,
                'refundable' => $faker->randomElement(['yes', 'no']), // Randomly choose refundable status
                'supplier_name' => $faker->company, // Generate random company name
                'retail_price' => $faker->randomFloat(2, 10, 150), // Generate random price between 10 and 150
                'discounted_price' => $faker->randomFloat(2, 0, 50), // Generate random discount between 0 and 50
                'unit_supplier_price' => $faker->randomFloat(2, 100, 500), // Generate random supplier price between 100 and 500
                'stocks' => $faker->numberBetween(50, 200), // Random stocks between 50 and 200
                'low_stocks' => 80,
                'moderate_stocks' => 90,
            ];
        }


        foreach ($items as $item) {

            // Prepare data for insertion
            $result_to_create = $this->helper->arrStoreMultipleData(
                $arr_to_store,
                $item,
                $this->fillable_attr_inventory_children->arrPayloadIdsToDecrypt(),
                [],
                $this->fillable_attr_inventory_children->arrFieldsToUppercase(),
            );
            info($result_to_create);

            // Create the InventoryProductModel instance with the selected attributes
            $created = InventoryProductModel::create($result_to_create);

            if (!$created) {
                $error_message = [
                    'message' => 'Failed to store inventory Parent',
                    'parameter' => $created,
                ];
                throw new \Exception(json_encode($error_message));
            }

            $this->helper->updateUniqueId($created, $this->fillable_attr_inventory_children->idToUpdate(), Str::uuid());

            info('ITEMS CHILD');
            info($created);
        }

        info('Finished inventoryChildTbl seeder method');
    }

    private function notificationTbl()
    {
        info('Starting notificationTbl seeder method');
        try {
            $items = [
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ],
                [
                    "user_id" => "18636745-ce34-4099-9442-f3216a62fa8c",
                    "tbl_reference_id" => "inventory_product_id-1804cf1d-8767-44d7-a4c1-0b394d66ddc5",
                    "name" => "LOW_STOCK",
                    "details" => json_encode([
                        "id" => 6,
                        "inventory_product_id" => "inventory_product_id-6d525858-a602-4b83-9dee-bff6b6e8c659",
                        "inventory_id" => "inventory_id-5d55b2b4-c5ce-4ccc-8a52-f64cf5184202",
                        "item_code" => "558564976976",
                        "image" => null,
                        "name" => "2B ALUMINUM LADDER 9 STEP",
                        "category" => "TOOLS",
                        "refundable" => "NO",
                        "supplier_name" => "ROSALEE MONAHAN",
                        "retail_price" => "100.00",
                        "discounted_price" => "0.00",
                        "unit_supplier_price" => "500.00",
                        "stocks" => 49,
                        "low_stocks" => 80,
                        "moderate_stocks" => 90,
                        "created_at" => "2024-09-09T11:49:46.000000Z",
                        "updated_at" => "2024-09-09T11:54:44.000000Z",
                        "deleted_at" => null
                    ]),
                    "is_read" => "0",
                ]
            ];

            foreach ($items as $index => $item) {
                // Prepare data for insertion
                $result_to_create = $this->helper->arrStoreMultipleData(
                    $this->fillable_attr_notification->arrToStores(),
                    $item,
                    [],
                    [],
                    []
                );

                info("Preparing notification data for index: {$index}");
                info($result_to_create);

                // Create the NotificationModel instance with the selected attributes
                $created = NotificationModel::create($result_to_create);

                // Log creation success or failure
                if ($created) {
                    info("Notification created successfully for index: {$index}");
                } else {
                    info("Failed to create notification for index: {$index}");
                }

                // Update unique ID
                $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_notification->idToUpdate(), Str::uuid());

                // Log and rollback if update fails
                if ($update_unique_id) {
                    info("Unique ID update failed for index: {$index}, rolling back.");
                    return $update_unique_id;
                }
            }

            info('Finished notificationTbl seeder method successfully');
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction in case of an error
            Log::error('Error creating Notification: ' . $e->getMessage());
            throw $e; // Re-throw the exception to bubble up to the run() method
        }
    }

    // private function voucherParentTbl()
    // {
    //     info('Starting voucherParentTbl seeder method');
    //     // Data to insert
    //     $items = [
    //         [
    //             'name' => "Acrylon",
    //             'category' => "Paint",
    //             'image' => null,
    //         ],
    //         [
    //             'name' => "Aluminum Ladder",
    //             'category' => "Tools",
    //             'image' => null,
    //         ],
    //     ];

    //     foreach ($items as $item) {
    //         // Prepare data for insertion
    //         $result_to_create = $this->helper->arrStoreMultipleData(
    //             $this->fillable_attr_inventorys->arrToStores(),
    //             $item,
    //             [],
    //             [],
    //             [],
    //         );

    //         // Create the InventoryModel instance with the selected attributes
    //         $created = InventoryModel::create($result_to_create);

    //         $update_unique_id = $this->helper->updateUniqueId($created, $this->fillable_attr_inventorys->idToUpdate(), Str::uuid());
    //         if ($update_unique_id) {
    //             DB::rollBack();
    //             return $update_unique_id;
    //         }

    //         if (!$created) {
    //             $error_message = [
    //                 'message' => 'Failed to store inventory Parent',
    //                 'parameter' => $created,
    //             ];
    //             throw new \Exception(json_encode($error_message));
    //         }
    //     }

    //     info('Finished inventoryParentTbl seeder method');
    // }
}
