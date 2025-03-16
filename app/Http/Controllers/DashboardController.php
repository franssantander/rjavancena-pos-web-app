<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Support\Str;
use App\Models\PaymentModel;
use Illuminate\Http\Request;
use App\Models\PurchaseModel;
use App\Models\UserInfoModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryProductModel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    protected $helper, $fillable_attr_purchase, $fillable_attr_inventory_product, $fillable_attr_payment;

    public function __construct(Helper $helper, PurchaseModel $fillable_attr_purchase, InventoryProductModel $fillable_attr_inventory_product, PaymentModel $fillable_attr_payment)
    {
        $this->helper = $helper;
        $this->fillable_attr_purchase = $fillable_attr_purchase;
        $this->fillable_attr_inventory_product = $fillable_attr_inventory_product;
        $this->fillable_attr_payment = $fillable_attr_payment;
    }


    public function dashboard(Request $request)
    {

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Retrieve data if not cached
        $response = [
            'message' => "Successfully retrieve data",
            'data' => $this->getRecentTransaction(),
            'today_sale' => $this->getSaleToday(),
            'today_transaction' => $this->getTodayTransaction(),
            'sale' => [
                $this->getSalesMonth(),
                $this->getSalesYear(),
                $this->getSaleTotalStock(),
            ],
            'chart' => [
                'today' => $this->getChartSalesToday(),
                'week' => $this->getChartSalesWeek(),
                'month' => $this->getChartSalesMonth(),
                'year' => $this->getChartSalesYear(),
                'decade' => $this->getChartSalesDecade(),
                // 'century' => $this->getChartSalesCentury(),
                // 'millennium' => $this->getChartSalesMillennium(),
            ],
        ];


        return response()->json($response, Response::HTTP_OK);
    }

    private function getSaleToday()
    {
        // Define the start and end times for today
        $start_of_day = Carbon::today()->startOfDay();  // 12:00 AM
        $end_of_day = Carbon::today()->endOfDay();      // 11:59 PM

        $arr_sale = [];
        $total_earn = PaymentModel::whereBetween('paid_at', [$start_of_day, $end_of_day])
            ->where('status', 'PAID')
            ->sum('total_amount');

        // total Earn
        $arr_sale['current'] = $total_earn;

        // Get today's sales total
        $arr_sale['today_chart'] = [[
            'name' => 'totalEarn',
            'value' => $total_earn,
        ]];

        $arr_sale['top_products'] =  $this->getSaleTodayTop5();

        return $arr_sale;
    }

    private function getSaleTodayTop5()
    {
        // Define the start and end times for today
        $start_of_day = Carbon::today()->startOfDay();  // 12:00 AM
        $end_of_day = Carbon::today()->endOfDay();      // 11:59 PM

        // Retrieve purchases with status 'PAID' within the defined time range
        $purchases = PurchaseModel::where('status', 'PAID')
            ->whereBetween('created_at', [$start_of_day, $end_of_day])
            ->get();

        // Initialize an array to store item counts
        $item_counts = [];

        // Count occurrences of each item
        foreach ($purchases as $purchase) {
            $item_code = $purchase->item_code;

            if (!isset($item_counts[$item_code])) {
                $item_counts[$item_code] = [
                    'image' => $purchase->image,
                    'name' => $purchase->name,
                    'category' => $purchase->category,
                    'price' => $purchase->retail_price,
                    'count' => 0
                ];
            }

            $item_counts[$item_code]['count']++;
        }

        // Sort items by count in descending order
        usort($item_counts, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Get the top 5 items
        $top_5_items = array_slice($item_counts, 0, 5);

        // Return the top 5 items
        return $top_5_items;
    }

    private function getSalesMonth()
    {

        $current_sale = PaymentModel::whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('total_amount');


        $previous_sale = PaymentModel::whereYear('paid_at', now()->subMonth()->year)
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->sum('total_amount');


        if ($previous_sale != 0) {
            $percentage_change = (($current_sale - $previous_sale) / abs($previous_sale)) * 100;
        } else {
            $percentage_change = ($current_sale != 0) ? 100 : 0;
        }

        return [
            'current' => $current_sale,
            'previous' => $previous_sale,
            'percent_change' => $percentage_change,
            'icon' => 'tabler:circle-filled',
            'card_icon' => 'tabler:cash',
            'title' => 'Monthly Sales',
        ];
    }

    private function getSalesYear()
    {

        $current_sale = PaymentModel::whereYear('paid_at', now()->year)
            ->sum('total_amount');


        $previous_sale = PaymentModel::whereYear('paid_at', now()->subYear()->year)
            ->sum('total_amount');


        if ($previous_sale != 0) {
            $percentage_change = (($current_sale - $previous_sale) / abs($previous_sale)) * 100;
        } else {
            $percentage_change = ($current_sale != 0) ? 100 : 0;
        }

        return [
            'current' => $current_sale,
            'previous' => $previous_sale,
            'percent_change' => $percentage_change,
            'icon' => 'tabler:trending-up',
            'card_icon' => 'tabler:moneybag',
            'title' => 'Yearly Sales',
        ];
    }

    private function getSaleTotalStock()
    {

        $total_stock = InventoryProductModel::sum('stocks');

        return [
            'icon' => 'tabler:trending-up',
            'current' => $total_stock,
            'title' => 'Total Products',
            'card_icon' => 'tabler:package',
        ];
    }

    private function getTodayTransaction()
    {
        $arr_today_transaction = [];

        // Get today's transactions
        $today_transactions = PaymentModel::whereDate('created_at', Carbon::now()->toDateString())->get()->toArray();

        // Check if there are any transactions for today
        if (!empty($today_transactions)) {
            // Loop through each transaction
            foreach ($today_transactions as $transaction) {
                // Initialize an array to store transaction attributes
                $transaction_data = [];

                // Assuming $this->fillable_attr_payment->getTodaysTranction() returns an array of attributes to fetch
                foreach ($this->fillable_attr_payment->getTodaysTranction() as $getTodaysTranction) {
                    // Check if the getTodaysTranction exists in the transaction
                    if (array_key_exists($getTodaysTranction, $transaction)) {
                        $value = $transaction[$getTodaysTranction];

                        // Check if the column needs formatting and value is not null
                        if (in_array($getTodaysTranction, $this->fillable_attr_payment->arrToConvertToReadableDateTime()) && $value !== null) {
                            $value = $this->helper->convertReadableTimeDate($value);
                        }

                        // Store the getTodaysTranction and its value in the transaction data array
                        $transaction_data[$getTodaysTranction] = $value;
                    }
                }

                // Add the transaction data to the array of today's transactions
                $arr_today_transaction[] = $transaction_data;
            }
        }

        return $arr_today_transaction;
    }

    private function getChartSalesToday()
    {
        // Get the current date using Carbon
        $current_date = Carbon::now();

        // Array to store hourly sales
        $hourly = [];

        // Loop through each hour of the day from 12 AM to 11 PM
        for ($hour = 0; $hour <= 23; $hour++) {
            // Create a Carbon instance for the current hour
            $current_hour = $current_date->copy()->hour($hour);

            // Get the start and end timestamps for the current hour
            $start_of_hour = $current_hour->copy()->startOfHour();
            $end_of_hour = $current_hour->copy()->endOfHour();

            // Get the sales total for the current hour
            // $hourly_total_sales = PaymentModel::whereBetween('paid_at', [$start_of_hour, $end_of_hour])
            //     ->sum('total_amount');

            // Get the sales total for the current hour
            $hourly_total_sales = PaymentModel::whereBetween('paid_at', [$start_of_hour, $end_of_hour])
                ->selectRaw('SUM(CASE WHEN total_amount = 0 THEN final_total_amount ELSE total_amount END) as total_amount')
                ->value('total_amount');

            // Format the hour in 12-hour format with AM/PM
            $formatted_hour = $current_hour->format('h A');

            // Add the hour and total sales to the array
            $hourly[] = [
                'hour' => $formatted_hour,
                'total' => $hourly_total_sales ?? 0,
            ];
        }

        return $hourly;
    }

    private function getChartSalesWeek()
    {
        // Array to store daily sales
        $daily_sales = [];

        // Get the start and end of the current week using Carbon
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Loop through each day of the week
        for ($day = $startOfWeek; $day <= $endOfWeek; $day->addDay()) {
            // Get the sales total for the current day
            // $total_sales = PaymentModel::whereDate('paid_at', $day)
            //     ->sum('total_amount');

            $total_sales = PaymentModel::whereDate('paid_at', $day)
                ->selectRaw('SUM(CASE WHEN total_amount = 0 THEN final_total_amount ELSE total_amount END) as total_amount')
                ->value('total_amount');


            // Add the day and total sales to the array
            $daily_sales[] = [
                'day' => $day->format('D'),
                'total' => $total_sales ?? 0,
            ];
        }

        return $daily_sales;
    }

    private function getChartSalesMonth()
    {
        // Array to store daily sales
        $daily_sales = [];

        // Get the current date using Carbon
        $current_date = Carbon::now();

        // Get the start and end of the current month
        $startOfMonth = $current_date->copy()->startOfMonth();
        $endOfMonth = $current_date->copy()->endOfMonth();

        // Loop through each day of the current month
        for ($day = 1; $day <= $endOfMonth->day; $day++) {
            // Create a Carbon instance for the current day
            $currentDay = Carbon::create($current_date->year, $current_date->month, $day);

            // Get the current day's sales total
            // $total_sales = PaymentModel::whereDate('paid_at', $currentDay)
            //     ->sum('total_amount');

            // Get the current day's sales total with conditional sum
            $total_sales = PaymentModel::whereDate('paid_at', $currentDay)
                ->selectRaw('SUM(CASE WHEN total_amount = 0 THEN final_total_amount ELSE total_amount END) as total_amount')
                ->value('total_amount');

            // Add the day and total sales to the array
            $daily_sales[] = [
                'day' => $currentDay->format('d'),
                'total' => $total_sales ?? 0,
            ];
        }

        return $daily_sales;
    }

    private function getChartSalesYear()
    {
        // Array to store monthly sales
        $monthly_sales = [];

        // Get the current year using Carbon
        $current_year = Carbon::now()->year;

        // Loop through each month using Carbon
        for ($month = 1; $month <= 12; $month++) {
            // Create a Carbon instance for the first day of the current month
            $startOfMonth = Carbon::create($current_year, $month, 1);
            // Get the last day of the current month
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            // Get the current month's sales total
            // $total_sales = PaymentModel::whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            //     ->sum('total_amount');

            $total_sales = PaymentModel::whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                ->selectRaw('SUM(CASE WHEN total_amount = 0 THEN final_total_amount ELSE total_amount END) as total_amount')
                ->value('total_amount');

            // Add the month name and total sales to the array
            $monthly_sales[] = [
                'name' => $startOfMonth->format('M'),
                'total' => $total_sales ?? 0,
            ];
        }

        return $monthly_sales;
    }

    private function getChartSalesDecade()
    {
        // Array to store yearly sales
        $decade_sale = [];

        // Get the earliest year from the PaymentModel
        $earliest_payment = PaymentModel::orderBy('paid_at', 'asc')->first();
        $start_year = $earliest_payment ? Carbon::parse($earliest_payment->paid_at)->year : Carbon::now()->year - 9;
        $current_year = Carbon::now()->year;

        // Loop through the next 10 years from the start year
        for ($year = $start_year; $year < $start_year + 10; $year++) {
            // Create a Carbon instance for the start of the year
            $startOfYear = Carbon::create($year, 1, 1);
            // Get the last day of the year
            $endOfYear = $startOfYear->copy()->endOfYear();

            // Get the year's sales total
            // $total_sales = PaymentModel::whereBetween('paid_at', [$startOfYear, $endOfYear])
            //     ->sum('total_amount');

            // Get the year's sales total with conditional sum
            $total_sales = PaymentModel::whereBetween('paid_at', [$startOfYear, $endOfYear])
                ->selectRaw('SUM(CASE WHEN total_amount = 0 THEN final_total_amount ELSE total_amount END) as total_amount')
                ->value('total_amount');

            // Add the year and total sales to the array
            $decade_sale[] = [
                'name' => (string) $year,
                'total' => $total_sales ?? 0,
            ];
        }

        return $decade_sale;
    }

    private function getChartSalesCentury()
    {
        // Array to store yearly sales
        $century_sale = [];

        // Get the earliest year from the PaymentModel
        $earliest_payment = PaymentModel::orderBy('paid_at', 'asc')->first();
        $start_year = $earliest_payment ? Carbon::parse($earliest_payment->paid_at)->year : Carbon::now()->year - 100;
        $current_year = Carbon::now()->year;

        // Loop through the next 100 years from the start year
        for ($year = $start_year; $year < $start_year + 100; $year++) {
            // Create a Carbon instance for the start of the year
            $startOfYear = Carbon::create($year, 1, 1);
            // Get the last day of the year
            $endOfYear = $startOfYear->copy()->endOfYear();

            // Get the year's sales total
            // $total_sales = PaymentModel::whereBetween('paid_at', [$startOfYear, $endOfYear])
            //     ->sum('total_amount');

            $total_sales = PaymentModel::whereBetween('paid_at', [$startOfYear, $endOfYear])
                ->selectRaw('SUM(CASE WHEN total_amount = 0 THEN final_total_amount ELSE total_amount END) as total_amount')
                ->value('total_amount');

            // Add the year and total sales to the array
            $century_sale[] = [
                'name' => (string) $year,
                'total' => $total_sales ?? 0,
            ];
        }

        return $century_sale;
    }

    private function getChartSalesMillennium()
    {
        // Array to store yearly sales
        $millennium_sale = [];

        // Get the earliest year from the PaymentModel
        $earliest_payment = PaymentModel::orderBy('paid_at', 'asc')->first();
        $start_year = $earliest_payment ? Carbon::parse($earliest_payment->paid_at)->year : Carbon::now()->year - 1000;
        $current_year = Carbon::now()->year;

        // Loop through the next 1000 years from the start year
        for ($year = $start_year; $year < $start_year + 1000; $year++) {
            // Create a Carbon instance for the start of the year
            $startOfYear = Carbon::create($year, 1, 1);
            // Get the last day of the year
            $endOfYear = $startOfYear->copy()->endOfYear();

            // Get the year's sales total
            // $total_sales = PaymentModel::whereBetween('paid_at', [$startOfYear, $endOfYear])
            //     ->sum('total_amount');

            $total_sales = PaymentModel::whereBetween('paid_at', [$startOfYear, $endOfYear])
                ->selectRaw('SUM(CASE WHEN total_amount = 0 THEN final_total_amount ELSE total_amount END) as total_amount')
                ->value('total_amount');

            // Add the year and total sales to the array
            $millennium_sale[] = [
                'name' => (string) $year,
                'total' => $total_sales ?? 0,
            ];
        }

        return $millennium_sale;
    }

    private function getRecentTransaction()
    {
        $crud_settings = $this->fillable_attr_payment->getApiCrudSettings();
        $arr_parent = [];
        $arr_all_items = [];

        $payments = PaymentModel::orderBy('created_at', 'desc')->get();
        foreach ($payments as $payment) {
            foreach ($this->fillable_attr_payment->getRecentTransaction() as $getRecentTransaction) {
                if ($getRecentTransaction == 'paid_at') {
                    $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction != null ? $this->helper->convertReadableTimeDate($payment->$getRecentTransaction) : null;
                } elseif ($getRecentTransaction == 'user_id') {
                    $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction;
                    $purchase = PurchaseModel::where('user_id_customer', $payment->user_id)
                        ->where('purchase_group_id', $payment->purchase_group_id)
                        ->first();
                    $arr_parent['customer_name'] = $purchase->customer_name ?? null;
                } elseif ($getRecentTransaction == 'status') {
                    // Initialize status_color if not already an array
                    if (!isset($arr_parent['status_color'])) {
                        $arr_parent['status_color'] = null;
                    }
                    // Loop through status_colors to find the matching status
                    foreach ($this->fillable_attr_payment->statusColorRecentTransaction() as $key => $statusColorRecentTransaction) {
                        if ($payment->$getRecentTransaction == $key) {
                            $arr_parent['status_color'] = $statusColorRecentTransaction;
                            break; // Exit the loop once the matching status is found
                        }
                    }

                    if ($payment->$getRecentTransaction == 'PAID') {
                        $arr_parent[$getRecentTransaction] = 'Paid';
                    } else {
                        $str_lowercase = strtolower($payment->$getRecentTransaction);
                        $arr_parent[$getRecentTransaction] = $this->helper->transformColumnName($str_lowercase);
                    }
                } else {
                    $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction;

                    // For Name of Cashier and Image
                    $purchase = PurchaseModel::where('user_id_customer', $payment->user_id)
                        ->where('purchase_group_id', $payment->purchase_group_id)
                        ->first();
                    // $user_info = UserInfoModel::where('user_id', $purchase->user_id_menu)
                    //     ->first();
                    // $arr_parent['cashier_name'] =
                    //     (isset($user_info->first_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->first_name)) : '') . ' ' .
                    //     (isset($user_info->last_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->last_name)) : '');
                    // $arr_parent['cashier_image'] =
                    //     (isset($user_info->image) ? env('PATH_FILE_USER_ACCOUNT') . Crypt::decrypt($user_info->image) : '');
                }
            }

            // Added void button
            if ($payment->status == 'PAID') {
                // Format Api
                $crud_action = $this->helper->formatApi(
                    $crud_settings['prefix'],
                    $crud_settings['payload'],
                    $crud_settings['method'],
                    $crud_settings['button_name'],
                    $crud_settings['icon'],
                    $crud_settings['container']
                );

                // Add the format Api Crud
                $arr_parent['actions'] = array_values($crud_action);

                // Add the payload value on actions
                foreach ($arr_parent['actions'] as &$action) {
                    $action = array_merge(
                        $action,
                        [
                            'payment_id' => Crypt::encrypt($payment->payment_id),
                            'user_id' => Crypt::encrypt($payment->user_id),
                            'customer_id' => $payment->user_id,
                            'purchase_group_id' => Crypt::encrypt($payment->purchase_group_id),
                            'status' => 'NOT PAID',
                        ]
                    );

                    // View
                    if ($action['button_name'] == 'View') {
                        // Initialize array to store purchase information
                        $grouped_purchases = [];

                        // Fetch purchases
                        $purchases = PurchaseModel::where('user_id_customer', Crypt::decrypt($action['user_id']))
                            ->where('purchase_group_id', Crypt::decrypt($action['purchase_group_id']))
                            ->where('status', 'PAID')
                            // ->orderBy('created_at', 'asc') // Add this line to sort by 'created_at' in ascending order
                            ->get();

                        // Loop through purchases 
                        foreach ($purchases as $purchase) {
                            // Generate a key based on the user_id_customer
                            $key = $purchase->user_id_customer;

                            // Check if the key already exists in the grouped purchases array
                            if (isset($grouped_purchases[$key])) {
                                // If the key exists, check if the same purchase details already exist
                                $found = false;
                                foreach ($grouped_purchases[$key] as &$grouped_purchase) {
                                    if (
                                        $grouped_purchase['purchase_group_id'] === $purchase->purchase_group_id &&
                                        $grouped_purchase['inventory_id'] === $purchase->inventory_id &&
                                        $grouped_purchase['inventory_product_id'] === $purchase->inventory_product_id &&
                                        $grouped_purchase['customer_name'] === $purchase->customer_name &&
                                        $grouped_purchase['item_code'] === $purchase->item_code &&
                                        $grouped_purchase['image'] === $purchase->image &&
                                        $grouped_purchase['name'] === $purchase->name &&
                                        $grouped_purchase['category'] === $purchase->category &&
                                        $grouped_purchase['retail_price'] === $purchase->retail_price &&
                                        $grouped_purchase['discounted_price'] === $purchase->discounted_price
                                    ) {
                                        // Initialize arr_purchase_id if it's not already set
                                        if (!isset($grouped_purchase['arr_purchase_id'])) {
                                            $grouped_purchase['arr_purchase_id'] = [];
                                        }
                                        $grouped_purchase['arr_purchase_id'][] = $purchase->purchase_id;

                                        if (!isset($grouped_purchase['action'])) {
                                            $grouped_purchase['action'] = [];
                                        }

                                        // If the same purchase details exist, increment the count and update total_price
                                        $grouped_purchase['count']++;
                                        if ($purchase->discounted_price != 0) {
                                            $grouped_purchase['total_price'] = $purchase->discounted_price * $grouped_purchase['count'];
                                        } else {
                                            $grouped_purchase['total_price'] = $purchase->retail_price * $grouped_purchase['count'];
                                        }

                                        $found = true;
                                        break;
                                    }
                                }
                                // If the same purchase details not found, add the new purchase details
                                if (!$found) {
                                    $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
                                    $grouped_purchases[$key][] = [
                                        'purchase_id' => $purchase->purchase_id,
                                        'purchase_group_id' => $purchase->purchase_group_id,
                                        'user_id_customer' => $purchase->user_id_customer,
                                        'inventory_id' => $purchase->inventory_id,
                                        'inventory_product_id' => $purchase->inventory_product_id,
                                        'customer_name' => $purchase->customer_name,
                                        'item_code' => $purchase->item_code,
                                        'image' => $purchase->image,
                                        'name' => $purchase->name,
                                        'category' => $purchase->category,
                                        'retail_price' => $purchase->retail_price,
                                        'discounted_price' => $purchase->discounted_price,
                                        'count' => 1,
                                        'total_price' => $total_price,
                                        'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
                                    ];
                                }
                            } else {
                                // If the key doesn't exist, initialize a new customer's purchases array    
                                $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
                                $grouped_purchases[$key][] = [
                                    'purchase_id' => $purchase->purchase_id,
                                    'purchase_group_id' => $purchase->purchase_group_id,
                                    'user_id_customer' => $purchase->user_id_customer,
                                    'inventory_id' => $purchase->inventory_id,
                                    'inventory_product_id' => $purchase->inventory_product_id,
                                    'customer_name' => $purchase->customer_name,
                                    'item_code' => $purchase->item_code,
                                    'image' => $purchase->image,
                                    'name' => $purchase->name,
                                    'category' => $purchase->category,
                                    'retail_price' => $purchase->retail_price,
                                    'discounted_price' => $purchase->discounted_price,
                                    'count' => 1,
                                    'total_price' => $total_price,
                                    'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
                                ];
                            }
                        }

                        // Prepare an array to hold each customer's data as objects
                        $formatted_data = [];

                        // Add payment information and format as objects
                        foreach ($grouped_purchases as $user_id_customer => $items) {
                            $customer_data = new \stdClass(); // Create a new stdClass object for each customer
                            $customer_data->customer_id = $user_id_customer;
                            $customer_data->customer_name = $items[0]['customer_name'];

                            $customer_data->total_orders = count($items); // Calculate total_orders as the number of unique items

                            $customer_data->items = [];

                            // Add items and format each as an object
                            foreach ($items as $item) {
                                $formatted_item = new \stdClass();
                                $formatted_item->item_code = $item['item_code'];
                                $formatted_item->name = $item['name'];
                                $formatted_item->category = $item['category'];
                                $formatted_item->image = $item['image'];
                                $formatted_item->retail_price = $item['retail_price'];
                                $formatted_item->discounted_price = $item['discounted_price'];
                                $formatted_item->count = $item['count'];
                                $formatted_item->total_price = $item['total_price'];
                                $formatted_item->stocks = InventoryProductModel::where('inventory_product_id', $item['inventory_product_id'])
                                    ->first()
                                    ->stocks;

                                $customer_data->items[] = $formatted_item;
                            }

                            $formatted_data[] = $customer_data;
                        }

                        $action['details'] = $formatted_data; // Add the purchase details to the main details array
                    }
                }
            } else {
                unset($arr_parent['actions']);
            }

            $arr_all_items[] = $arr_parent;
        }

        // Final response structure
        $response = [
            'recent_transactions' => $arr_all_items,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_payment->columnHeader()),
        ];

        return $response;
    }

    public function getRecentTransactionCashierRole(Request $request)
    {
        $crud_settings = $this->fillable_attr_payment->getApiCrudSettings();
        $arr_parent = [];
        $arr_all_items = [];

        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // $payments = PaymentModel::where('user_id_menu', $user->user_id)->orderBy('created_at', 'desc')->get();

        // Fetch paginated
        $payments = PaymentModel::where('user_id_menu', $user->user_id)->orderBy('created_at', 'desc');

        // Apply date range filters for created_at if provided
        if ($request->query('created_start_at') != '' || $request->query('created_end_at') != '') {
            if ($request->query('created_start_at') != '') {
                $payments = $payments->where('created_at', '>=', $request->query('created_start_at'));
            }
            if ($request->query('created_end_at') != '') {
                $payments = $payments->where('created_at', '<=', $request->query('created_end_at'));
            }
        }

        // Apply date range filters for updated_at if provided
        if ($request->query('updated_start_at') != '' || $request->query('updated_end_at') != '') {
            if ($request->query('updated_start_at') != '') {
                $payments = $payments->where('updated_at', '>=', $request->query('updated_start_at'));
            }
            if ($request->query('updated_end_at') != '') {
                $payments = $payments->where('updated_at', '<=', $request->query('updated_end_at'));
            }
        }

        // Apply date range filters for deleted_at if provided
        if ($request->query('deleted_start_at') != '' || $request->query('deleted_end_at') != '') {
            if ($request->query('deleted_start_at') != '') {
                $payments = $payments->where('deleted_at', '>=', $request->query('deleted_start_at'));
            }
            if ($request->query('deleted_end_at') != '') {
                $payments = $payments->where('deleted_at', '<=', $request->query('deleted_end_at'));
            }
        }

        // status
        if ($request->query('status') != '' && in_array($request->query('status'), ['NOT PAID', 'PAID'])) {
            $payments = $payments->where('status', Str::upper($request->query('status')));
        }

        // Apply pagination
        $payments = $payments->paginate(
            $request->query('limit', 10), // Items per page
            ['*'], // Select all columns
            'page', // Pagination parameter name
            $request->query('page', 1) // Current page
        );

        foreach ($payments as $payment) {
            foreach ($this->fillable_attr_payment->getRecentTransaction() as $getRecentTransaction) {
                if ($getRecentTransaction == 'paid_at') {
                    $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction != null ? $this->helper->convertReadableTimeDate($payment->$getRecentTransaction) : null;
                } elseif ($getRecentTransaction == 'user_id') {
                    $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction;
                    $purchase = PurchaseModel::where('user_id_customer', $payment->user_id)
                        ->where('purchase_group_id', $payment->purchase_group_id)
                        ->where('user_id_menu', $user->user_id)
                        ->first();
                    $arr_parent['customer_name'] = $purchase->customer_name ?? null;
                } elseif ($getRecentTransaction == 'status') {
                    // Initialize status_color if not already an array
                    if (!isset($arr_parent['status_color'])) {
                        $arr_parent['status_color'] = null;
                    }
                    // Loop through status_colors to find the matching status
                    foreach ($this->fillable_attr_payment->statusColorRecentTransaction() as $key => $statusColorRecentTransaction) {
                        if ($payment->$getRecentTransaction == $key) {
                            $arr_parent['status_color'] = $statusColorRecentTransaction;
                            break; // Exit the loop once the matching status is found
                        }
                    }

                    if ($payment->$getRecentTransaction == 'PAID') {
                        $arr_parent[$getRecentTransaction] = 'Paid';
                    } else {
                        $str_lowercase = strtolower($payment->$getRecentTransaction);
                        $arr_parent[$getRecentTransaction] = $this->helper->transformColumnName($str_lowercase);
                    }
                } else {
                    $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction;

                    // For Name of Cashier and Image
                    $purchase = PurchaseModel::where('user_id_customer', $payment->user_id)
                        ->where('purchase_group_id', $payment->purchase_group_id)
                        ->where('user_id_menu', $user->user_id)
                        ->first();
                    // $user_info = UserInfoModel::where('user_id', $purchase->user_id_menu)
                    //     ->first();
                    // $arr_parent['cashier_name'] =
                    //     (isset($user_info->first_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->first_name)) : '') . ' ' .
                    //     (isset($user_info->last_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->last_name)) : '');
                    // $arr_parent['cashier_image'] =
                    //     (isset($user_info->image) ? env('PATH_FILE_USER_ACCOUNT') . Crypt::decrypt($user_info->image) : '');
                }
            }

            // Added void button
            if ($payment->status == 'PAID') {
                // Format Api
                $crud_action = $this->helper->formatApi(
                    $crud_settings['prefix'],
                    $crud_settings['payload'],
                    $crud_settings['method'],
                    $crud_settings['button_name'],
                    $crud_settings['icon'],
                    $crud_settings['container']
                );

                // Add the format Api Crud
                $arr_parent['actions'] = array_values($crud_action);

                // Add the payload value on actions
                foreach ($arr_parent['actions'] as &$action) {
                    $action = array_merge(
                        $action,
                        [
                            'payment_id' => Crypt::encrypt($payment->payment_id),
                            'user_id' => Crypt::encrypt($payment->user_id),
                            'customer_id' => $payment->user_id,
                            'purchase_group_id' => Crypt::encrypt($payment->purchase_group_id),
                            'status' => 'NOT PAID',
                        ]
                    );

                    // View
                    if ($action['button_name'] == 'View') {
                        // Initialize array to store purchase information
                        $grouped_purchases = [];

                        // Fetch purchases
                        $purchases = PurchaseModel::where('user_id_customer', Crypt::decrypt($action['user_id']))
                            ->where('purchase_group_id', Crypt::decrypt($action['purchase_group_id']))
                            ->where('user_id_menu', $user->user_id)
                            ->where('status', 'PAID')
                            // ->orderBy('created_at', 'asc') // Add this line to sort by 'created_at' in ascending order
                            ->get();

                        // Loop through purchases 
                        foreach ($purchases as $purchase) {
                            // Generate a key based on the user_id_customer
                            $key = $purchase->user_id_customer;

                            // Check if the key already exists in the grouped purchases array
                            if (isset($grouped_purchases[$key])) {
                                // If the key exists, check if the same purchase details already exist
                                $found = false;
                                foreach ($grouped_purchases[$key] as &$grouped_purchase) {
                                    if (
                                        $grouped_purchase['purchase_group_id'] === $purchase->purchase_group_id &&
                                        $grouped_purchase['inventory_id'] === $purchase->inventory_id &&
                                        $grouped_purchase['inventory_product_id'] === $purchase->inventory_product_id &&
                                        $grouped_purchase['customer_name'] === $purchase->customer_name &&
                                        $grouped_purchase['item_code'] === $purchase->item_code &&
                                        $grouped_purchase['image'] === $purchase->image &&
                                        $grouped_purchase['name'] === $purchase->name &&
                                        $grouped_purchase['category'] === $purchase->category &&
                                        $grouped_purchase['retail_price'] === $purchase->retail_price &&
                                        $grouped_purchase['discounted_price'] === $purchase->discounted_price
                                    ) {
                                        // Initialize arr_purchase_id if it's not already set
                                        if (!isset($grouped_purchase['arr_purchase_id'])) {
                                            $grouped_purchase['arr_purchase_id'] = [];
                                        }
                                        $grouped_purchase['arr_purchase_id'][] = $purchase->purchase_id;

                                        if (!isset($grouped_purchase['action'])) {
                                            $grouped_purchase['action'] = [];
                                        }

                                        // If the same purchase details exist, increment the count and update total_price
                                        $grouped_purchase['count']++;
                                        if ($purchase->discounted_price != 0) {
                                            $grouped_purchase['total_price'] = $purchase->discounted_price * $grouped_purchase['count'];
                                        } else {
                                            $grouped_purchase['total_price'] = $purchase->retail_price * $grouped_purchase['count'];
                                        }

                                        $found = true;
                                        break;
                                    }
                                }
                                // If the same purchase details not found, add the new purchase details
                                if (!$found) {
                                    $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
                                    $grouped_purchases[$key][] = [
                                        'purchase_id' => $purchase->purchase_id,
                                        'purchase_group_id' => $purchase->purchase_group_id,
                                        'user_id_customer' => $purchase->user_id_customer,
                                        'inventory_id' => $purchase->inventory_id,
                                        'inventory_product_id' => $purchase->inventory_product_id,
                                        'customer_name' => $purchase->customer_name,
                                        'item_code' => $purchase->item_code,
                                        'image' => $purchase->image,
                                        'name' => $purchase->name,
                                        'category' => $purchase->category,
                                        'retail_price' => $purchase->retail_price,
                                        'discounted_price' => $purchase->discounted_price,
                                        'count' => 1,
                                        'total_price' => $total_price,
                                        'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
                                    ];
                                }
                            } else {
                                // If the key doesn't exist, initialize a new customer's purchases array    
                                $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
                                $grouped_purchases[$key][] = [
                                    'purchase_id' => $purchase->purchase_id,
                                    'purchase_group_id' => $purchase->purchase_group_id,
                                    'user_id_customer' => $purchase->user_id_customer,
                                    'inventory_id' => $purchase->inventory_id,
                                    'inventory_product_id' => $purchase->inventory_product_id,
                                    'customer_name' => $purchase->customer_name,
                                    'item_code' => $purchase->item_code,
                                    'image' => $purchase->image,
                                    'name' => $purchase->name,
                                    'category' => $purchase->category,
                                    'retail_price' => $purchase->retail_price,
                                    'discounted_price' => $purchase->discounted_price,
                                    'count' => 1,
                                    'total_price' => $total_price,
                                    'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
                                ];
                            }
                        }

                        // Prepare an array to hold each customer's data as objects
                        $formatted_data = [];

                        // Add payment information and format as objects
                        foreach ($grouped_purchases as $user_id_customer => $items) {
                            $customer_data = new \stdClass(); // Create a new stdClass object for each customer
                            $customer_data->customer_id = $user_id_customer;
                            $customer_data->customer_name = $items[0]['customer_name'];

                            $customer_data->total_orders = count($items); // Calculate total_orders as the number of unique items

                            $customer_data->items = [];

                            // Add items and format each as an object
                            foreach ($items as $item) {
                                $formatted_item = new \stdClass();
                                $formatted_item->item_code = $item['item_code'];
                                $formatted_item->name = $item['name'];
                                $formatted_item->category = $item['category'];
                                $formatted_item->image = $item['image'];
                                $formatted_item->retail_price = $item['retail_price'];
                                $formatted_item->discounted_price = $item['discounted_price'];
                                $formatted_item->count = $item['count'];
                                $formatted_item->total_price = $item['total_price'];
                                $formatted_item->stocks = InventoryProductModel::where('inventory_product_id', $item['inventory_product_id'])
                                    ->first()
                                    ->stocks;

                                $customer_data->items[] = $formatted_item;
                            }

                            $formatted_data[] = $customer_data;
                        }

                        $action['details'] = $formatted_data; // Add the purchase details to the main details array
                    }
                }
            } else {
                unset($arr_parent['actions']);
            }

            $arr_all_items[] = $arr_parent;
        }

        // Final response structure
        $response = [
            'recent_transactions' => $arr_all_items,
            'columns' => $this->helper->transformColumnName($this->fillable_attr_payment->columnHeader()),
            'pagination' => [
                'count' => $payments->count(),
                'has_page' => $payments->hasPages(),
                'has_more_pages' => $payments->hasMorePages(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'next_page_url' => $payments->nextPageUrl(),
                'previous_page_url' => $payments->previousPageUrl(),
            ],
        ];

        return response()->json(
            [
                'message' => 'Success retrieve data',
                'data' => $response,
            ],
            Response::HTTP_OK
        );
    }

    // public function getRecentTransactionCashierRolee(Request $request)
    // {
    //     $crud_settings = $this->fillable_attr_payment->getApiCrudSettings();
    //     $arr_parent = [];
    //     $arr_all_items = [];

    //     // Authorize the user
    //     $user = $this->helper->authorizeUser($request);
    //     if (empty($user->user_id)) {
    //         return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     $purchases = PurchaseModel::where('user_id_menu', $user->user_id)
    //         ->get();
    //     foreach ($purchases as $purchase) {
    //         foreach ($this->fillable_attr_payment->getRecentTransaction() as $getRecentTransaction) {
    //             $payment = PaymentModel::where('user_id', $purchase->user_id_customer)
    //                 ->where('purchase_group_id', $purchase->purchase_group_id)
    //                 ->first();

    //             if ($getRecentTransaction == 'paid_at') {
    //                 $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction != null ? $this->helper->convertReadableTimeDate($payment->$getRecentTransaction) : null;
    //             } elseif ($getRecentTransaction == 'user_id') {
    //                 $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction;
    //                 $purchase = PurchaseModel::where('user_id_customer', $payment->user_id)
    //                     ->where('purchase_group_id', $payment->purchase_group_id)
    //                     ->where('user_id_menu', $user->user_id)
    //                     ->first();
    //                 $arr_parent['customer_name'] = $purchase->customer_name ?? null;
    //             } elseif ($getRecentTransaction == 'status') {
    //                 // Initialize status_color if not already an array
    //                 if (!isset($arr_parent['status_color'])) {
    //                     $arr_parent['status_color'] = null;
    //                 }
    //                 // Loop through status_colors to find the matching status
    //                 foreach ($this->fillable_attr_payment->statusColorRecentTransaction() as $key => $statusColorRecentTransaction) {
    //                     if ($payment->$getRecentTransaction == $key) {
    //                         $arr_parent['status_color'] = $statusColorRecentTransaction;
    //                         break; // Exit the loop once the matching status is found
    //                     }
    //                 }

    //                 if ($payment->$getRecentTransaction == 'PAID') {
    //                     $arr_parent[$getRecentTransaction] = 'Paid';
    //                 } else {
    //                     $str_lowercase = strtolower($payment->$getRecentTransaction);
    //                     $arr_parent[$getRecentTransaction] = $this->helper->transformColumnName($str_lowercase);
    //                 }
    //             } else {
    //                 $arr_parent[$getRecentTransaction] = $payment->$getRecentTransaction;

    //                 // For Name of Cashier and Image
    //                 $purchase = PurchaseModel::where('user_id_customer', $payment->user_id)
    //                     ->where('purchase_group_id', $payment->purchase_group_id)
    //                     ->first();
    //                 // $user_info = UserInfoModel::where('user_id', $purchase->user_id_menu)
    //                 //     ->first();
    //                 // $arr_parent['cashier_name'] =
    //                 //     (isset($user_info->first_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->first_name)) : '') . ' ' .
    //                 //     (isset($user_info->last_name) ? $this->helper->transformColumnName(Crypt::decrypt($user_info->last_name)) : '');
    //                 // $arr_parent['cashier_image'] =
    //                 //     (isset($user_info->image) ? env('PATH_FILE_USER_ACCOUNT') . Crypt::decrypt($user_info->image) : '');
    //             }
    //         }

    //         // Added void button
    //         if ($payment->status == 'PAID') {
    //             // Format Api
    //             $crud_action = $this->helper->formatApi(
    //                 $crud_settings['prefix'],
    //                 $crud_settings['payload'],
    //                 $crud_settings['method'],
    //                 $crud_settings['button_name'],
    //                 $crud_settings['icon'],
    //                 $crud_settings['container']
    //             );

    //             // Add the format Api Crud
    //             $arr_parent['actions'] = array_values($crud_action);

    //             // Add the payload value on actions
    //             foreach ($arr_parent['actions'] as &$action) {
    //                 $action = array_merge(
    //                     $action,
    //                     [
    //                         'payment_id' => Crypt::encrypt($payment->payment_id),
    //                         'user_id' => Crypt::encrypt($payment->user_id),
    //                         'customer_id' => $payment->user_id,
    //                         'purchase_group_id' => Crypt::encrypt($payment->purchase_group_id),
    //                         'status' => 'NOT PAID',
    //                     ]
    //                 );

    //                 // View
    //                 if ($action['button_name'] == 'View') {
    //                     // Initialize array to store purchase information
    //                     $grouped_purchases = [];

    //                     // Fetch purchases
    //                     $purchases = PurchaseModel::where('user_id_customer', Crypt::decrypt($action['user_id']))
    //                         ->where('purchase_group_id', Crypt::decrypt($action['purchase_group_id']))
    //                         ->where('status', 'PAID')
    //                         ->where('user_id_menu', $user->user_id)
    //                         // ->orderBy('created_at', 'asc') // Add this line to sort by 'created_at' in ascending order
    //                         ->get();

    //                     // Loop through purchases 
    //                     foreach ($purchases as $purchase) {
    //                         // Generate a key based on the user_id_customer
    //                         $key = $purchase->user_id_customer;

    //                         // Check if the key already exists in the grouped purchases array
    //                         if (isset($grouped_purchases[$key])) {
    //                             // If the key exists, check if the same purchase details already exist
    //                             $found = false;
    //                             foreach ($grouped_purchases[$key] as &$grouped_purchase) {
    //                                 if (
    //                                     $grouped_purchase['purchase_group_id'] === $purchase->purchase_group_id &&
    //                                     $grouped_purchase['inventory_id'] === $purchase->inventory_id &&
    //                                     $grouped_purchase['inventory_product_id'] === $purchase->inventory_product_id &&
    //                                     $grouped_purchase['customer_name'] === $purchase->customer_name &&
    //                                     $grouped_purchase['item_code'] === $purchase->item_code &&
    //                                     $grouped_purchase['image'] === $purchase->image &&
    //                                     $grouped_purchase['name'] === $purchase->name &&
    //                                     $grouped_purchase['category'] === $purchase->category &&
    //                                     $grouped_purchase['retail_price'] === $purchase->retail_price &&
    //                                     $grouped_purchase['discounted_price'] === $purchase->discounted_price
    //                                 ) {
    //                                     // Initialize arr_purchase_id if it's not already set
    //                                     if (!isset($grouped_purchase['arr_purchase_id'])) {
    //                                         $grouped_purchase['arr_purchase_id'] = [];
    //                                     }
    //                                     $grouped_purchase['arr_purchase_id'][] = $purchase->purchase_id;

    //                                     if (!isset($grouped_purchase['action'])) {
    //                                         $grouped_purchase['action'] = [];
    //                                     }

    //                                     // If the same purchase details exist, increment the count and update total_price
    //                                     $grouped_purchase['count']++;
    //                                     if ($purchase->discounted_price != 0) {
    //                                         $grouped_purchase['total_price'] = $purchase->discounted_price * $grouped_purchase['count'];
    //                                     } else {
    //                                         $grouped_purchase['total_price'] = $purchase->retail_price * $grouped_purchase['count'];
    //                                     }

    //                                     $found = true;
    //                                     break;
    //                                 }
    //                             }
    //                             // If the same purchase details not found, add the new purchase details
    //                             if (!$found) {
    //                                 $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
    //                                 $grouped_purchases[$key][] = [
    //                                     'purchase_id' => $purchase->purchase_id,
    //                                     'purchase_group_id' => $purchase->purchase_group_id,
    //                                     'user_id_customer' => $purchase->user_id_customer,
    //                                     'inventory_id' => $purchase->inventory_id,
    //                                     'inventory_product_id' => $purchase->inventory_product_id,
    //                                     'customer_name' => $purchase->customer_name,
    //                                     'item_code' => $purchase->item_code,
    //                                     'image' => $purchase->image,
    //                                     'name' => $purchase->name,
    //                                     'category' => $purchase->category,
    //                                     'retail_price' => $purchase->retail_price,
    //                                     'discounted_price' => $purchase->discounted_price,
    //                                     'count' => 1,
    //                                     'total_price' => $total_price,
    //                                     'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
    //                                 ];
    //                             }
    //                         } else {
    //                             // If the key doesn't exist, initialize a new customer's purchases array    
    //                             $total_price = ($purchase->discounted_price != 0) ? $purchase->discounted_price : $purchase->retail_price;
    //                             $grouped_purchases[$key][] = [
    //                                 'purchase_id' => $purchase->purchase_id,
    //                                 'purchase_group_id' => $purchase->purchase_group_id,
    //                                 'user_id_customer' => $purchase->user_id_customer,
    //                                 'inventory_id' => $purchase->inventory_id,
    //                                 'inventory_product_id' => $purchase->inventory_product_id,
    //                                 'customer_name' => $purchase->customer_name,
    //                                 'item_code' => $purchase->item_code,
    //                                 'image' => $purchase->image,
    //                                 'name' => $purchase->name,
    //                                 'category' => $purchase->category,
    //                                 'retail_price' => $purchase->retail_price,
    //                                 'discounted_price' => $purchase->discounted_price,
    //                                 'count' => 1,
    //                                 'total_price' => $total_price,
    //                                 'arr_purchase_id' => [$purchase->purchase_id], // Initialize arr_purchase_id with the first purchase ID
    //                             ];
    //                         }
    //                     }

    //                     // Prepare an array to hold each customer's data as objects
    //                     $formatted_data = [];

    //                     // Add payment information and format as objects
    //                     foreach ($grouped_purchases as $user_id_customer => $items) {
    //                         $customer_data = new \stdClass(); // Create a new stdClass object for each customer
    //                         $customer_data->customer_id = $user_id_customer;
    //                         $customer_data->customer_name = $items[0]['customer_name'];

    //                         $customer_data->total_orders = count($items); // Calculate total_orders as the number of unique items

    //                         $customer_data->items = [];

    //                         // Add items and format each as an object
    //                         foreach ($items as $item) {
    //                             $formatted_item = new \stdClass();
    //                             $formatted_item->item_code = $item['item_code'];
    //                             $formatted_item->name = $item['name'];
    //                             $formatted_item->category = $item['category'];
    //                             $formatted_item->image = $item['image'];
    //                             $formatted_item->retail_price = $item['retail_price'];
    //                             $formatted_item->discounted_price = $item['discounted_price'];
    //                             $formatted_item->count = $item['count'];
    //                             $formatted_item->total_price = $item['total_price'];
    //                             $formatted_item->stocks = InventoryProductModel::where('inventory_product_id', $item['inventory_product_id'])
    //                                 ->first()
    //                                 ->stocks;

    //                             $customer_data->items[] = $formatted_item;
    //                         }

    //                         $formatted_data[] = $customer_data;
    //                     }

    //                     $action['details'] = $formatted_data; // Add the purchase details to the main details array
    //                 }
    //             }
    //         } else {
    //             unset($arr_parent['actions']);
    //         }

    //         $arr_all_items[] = $arr_parent;
    //     }

    //     // Final response structure
    //     $response = [
    //         'recent_transactions' => $arr_all_items,
    //         'columns' => $this->helper->transformColumnName($this->fillable_attr_payment->columnHeader()),
    //     ];

    //     return response()->json(
    //         [
    //             'message' => 'Success retrieve data',
    //             'data' => $response,
    //         ],
    //         Response::HTTP_OK
    //     );
    // }

    public function updateStatusVoid(Request $request)
    {
        // Authorize the user
        $user = $this->helper->authorizeUser($request);
        if (empty($user->user_id)) {
            return response()->json(['message' => 'Not authenticated user'], Response::HTTP_UNAUTHORIZED);
        }

        // Validation rules for each item in the array
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|string',
            'user_id' => 'required|string',
            'purchase_group_id' => 'required|string',
            'status' => 'required|string|max:255|in:NOT PAID',
            'eu_device' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Validate eu_device
        $result_validate_eu_device = $this->helper->validateEuDevice($request->input('eu_device'));
        if ($result_validate_eu_device) {
            return $result_validate_eu_device;
        }

        $decrypt_payment_id = Crypt::decrypt($request->payment_id);
        $decrypt_user_id = Crypt::decrypt($request->user_id);
        $decrypt_purchase_group_id = Crypt::decrypt($request->purchase_group_id);

        // Begin transaction
        DB::beginTransaction();

        try {
            $payment = PaymentModel::where('payment_id', $decrypt_payment_id)
                ->where('payment_id', $decrypt_payment_id)
                ->where('user_id', $decrypt_user_id)
                ->where('purchase_group_id', $decrypt_purchase_group_id)
                ->first();

            $user_logs['payment_old'] = $payment->toArray();

            if (!$payment) {
                return response()->json(
                    [
                        'message' => 'Invalid payment model'
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            if (!$payment->update([
                'change' => 0.00,
                'money' => 0.00,
                'status' => 'NOT PAID',
                'paid_at' => null,
            ])) {
                DB::rollBack();
                return response()->json(
                    [
                        'message' => 'Failed to update status on payment'
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            $user_logs['payment_update'] = $payment->toArray();

            $ctr = 0;
            $purchases = PurchaseModel::where('purchase_group_id', $decrypt_purchase_group_id)
                ->where('user_id_customer', $decrypt_user_id)->get();
            foreach ($purchases as $purchase) {
                $inventory_product = InventoryProductModel::where('inventory_id', $purchase->inventory_id)
                    ->where('inventory_product_id', $purchase->inventory_product_id)->first();

                if (!$inventory_product) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'message' => 'Data not found on Inventory product tbl'
                        ],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }


                if (!$purchase->update([
                    'status' => $request->status,
                ])) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'message' => 'Failed to update status on purchase'
                        ],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                $user_logs['items_purchase-' . $ctr] = $purchase->toArray();
                $ctr++;
            }

            // *********************************** //
            // Start log
            // Logs
            $log_result = $this->helper->log(
                $request,
                [
                    'user_device' => $request->eu_device,
                    'user_id' => $user->user_id,
                    'is_history' => 0,
                    'user_action' => 'VOID_ITEM_PURCHASE_AND_PAYMENT',
                ],
                $user_logs,
                5,
                null
            );

            // Failed to create log
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                DB::rollBack(); // Rollback transaction
                return $log_result;
            }
            // End log
            // *********************************** //


            DB::commit();
            return response()->json([
                'message' => 'Status update successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
