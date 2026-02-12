<?php

namespace App\Http\Livewire\Admin\Order;

use DB;
use Hash;
use Auth;
use Session;
use Validator;
use App\Models\User;
use App\Models\Product;
use App\Models\Product2;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\ProductOrder;
use App\Models\OrderDueAmount;
use App\Models\OrderDueAmount2;
use App\Models\ProductOrder2;
use App\Models\ReturnProduct;
use App\Models\ReturnProduct2;
use App\Models\ProductOrderDetails;
use App\Models\ProductOrderDetails2;
use App\Models\PurchaseReport as ModelPurchaseReport;
use App\Models\PurchaseReport2 as ModelPurchaseReport2;
use App\Http\Livewire\Traits\AlertMessage;

class OrderList extends Component
{

    use WithPagination;
    use AlertMessage;
    public $searchName, $dateForm, $dateTo, $viewOrder, $product_name, $total_profit, $product_code, $product_qty, $product_selling_price, $return_order_id, $perPage, $total_purchase, $returnOrder, $setting, $storeUser, $user, $viewDueOrderDetails, $state = [];
    protected $listeners = ['deleteConfirm', 'loadMore'];
    protected $paginationTheme = 'bootstrap';
    public function mount()
    {
        $this->perPage = env('PER_PAGE', 50);
        $this->dateForm = date('Y-m-d');
        $this->dateTo = date('Y-m-d');
        $this->setting = Setting::first();
        $this->user = Auth::user();
        if (Auth::user()->type == 'A') {
            $this->storeUser = 1;
            $store = Session::get('store');
            if ($store) {
                $this->storeUser = $store;
            }
        } else {

            $this->storeUser = Auth::user()->store;
        }
    }

    public function updatedStoreUser($value)
    {
        Session::put('store', $value);
    }
    public function loadMore()
    {
        $this->perPage = $this->perPage + env('PER_PAGE', 50);
    }

    public function resetSearch()
    {
        $this->dateForm = null;
        $this->searchName = null;
        $this->dateTo = null;
    }

    public function viewOrders($id)
    {
        if ($this->storeUser == 1)
            $this->viewOrder = ProductOrderDetails::with('productDetails.product', 'returnProducts.product')->withCount('due_payments')->find($id);
        else
            $this->viewOrder = ProductOrderDetails2::with('productDetails.product', 'returnProducts.product')->withCount('due_payments')->find($id);

        //dd($this->viewOrder);
        $this->dispatchBrowserEvent('show-order-view-form');
    }

    public function saveReturnOrder()
    {
        if ($this->storeUser == 1) {
            $order = ProductOrder::with('customer')->find($this->return_order_id);
            $product = Product::find($order->product_id);
        } else {
            $order = ProductOrder2::with('customer')->find($this->return_order_id);
            $product = Product2::find($order->product_id);
        }


        $this->validate([
            'product_qty' => 'required|integer|between:1,' . $order->qty
        ], ['product_qty.between' => 'Enter valid quantity']);

        if ($order->qty == $this->product_qty) {
            if ($this->storeUser == 1) {
                $data = ReturnProduct::firstOrCreate([
                    'order_id' => $order->order_id,
                    'product_id' => $order->product_id
                ]);
            } else {
                $data = ReturnProduct2::firstOrCreate([
                    'order_id' => $order->order_id,
                    'product_id' => $order->product_id
                ]);
            }

            $data->update([
                'product_name' => $order->product_name,
                'product_code' => $order->product_code,
                'qty' => ($data->qty + $order->qty),
                'selling_price' => $order->selling_price,
                'discount' => $order->discount,
                'purchase_price' => $order->purchase_price,
                'price' => ($order->selling_price * $this->product_qty),
                /*'price' => ($data->price+(($order->selling_price*$this->product_qty)-($order->discount*$this->product_qty))),*/
                'customer_contact' => $order->customer->customer_phone,
                'date' => date('Y-m-d'),
                'status' => 'active',
            ]);
            if ($data) {
                $product->update([
                    'quantity' => ($product->quantity + (int)$this->product_qty),
                ]);
                $discount_amt = 0.00;
                $perctge_amt = 0.00;
                if ($order->customer->discount_amt != '0.00') {
                    $discount_amt = $order->customer->discount_amt - ($order->discount * $this->product_qty);
                }
                if ($order->customer->perctge_amt != '0.00') {
                    $perctge_amt = $order->customer->perctge_amt - ($order->discount * $this->product_qty);
                }
                /* $order->customer()->update([
                    'subtotal' => ($order->customer->subtotal-($order->selling_price*$this->product_qty)),
                    'discount_amt' => $discount_amt,
                    'perctge_amt' => $perctge_amt,
                    'total_amount' => ($order->customer->total_amount-(($order->selling_price-$order->discount)*$this->product_qty)),

                ]); */
                $order->delete();
            }
            $this->showToastr("success", 'Product retured successfully');
            return redirect()->route('order_list');
        } else {
            if ($this->storeUser == 1) {
                $data = ReturnProduct::firstOrCreate([
                    'order_id' => $order->order_id,
                    'product_id' => $order->product_id
                ]);
            } else {
                $data = ReturnProduct2::firstOrCreate([
                    'order_id' => $order->order_id,
                    'product_id' => $order->product_id
                ]);
            }


            $data->update([
                'product_name' => $order->product_name,
                'product_code' => $order->product_code,
                'qty' => ($data->qty + $this->product_qty),
                'selling_price' => $order->selling_price,
                'discount' => $order->discount,
                'purchase_price' => $order->purchase_price,
                'price' => ($order->selling_price * $this->product_qty),
                /*'price' => ($data->price+(($order->selling_price*$this->product_qty)-($order->discount*$this->product_qty))),*/
                'customer_contact' => $order->customer->customer_phone,
                'status' => 'active',
            ]);
            if ($data) {
                if ($this->storeUser == 1)
                    $product = Product::find($order->product_id);
                else
                    $product = Product2::find($order->product_id);

                $product->update(['quantity' => ($product->quantity + (int)$this->product_qty)]);
                $order->update([
                    'qty' => $order->qty - $this->product_qty,
                    'subtotal' => $order->selling_price * ($order->qty - $this->product_qty),
                ]);
                $discount_amt = 0.00;
                $perctge_amt = 0.00;
                if ($order->customer->discount_amt != '0.00') {
                    $discount_amt = $order->customer->discount_amt - ($order->discount * $this->product_qty);
                }
                if ($order->customer->perctge_amt != '0.00') {
                    $perctge_amt = $order->customer->perctge_amt - ($order->discount * $this->product_qty);
                }
                /* $order->customer()->update([
                    'subtotal' => ($order->customer->subtotal-($order->selling_price*$this->product_qty)),
                    'discount_amt' => $discount_amt,
                    'perctge_amt' => $perctge_amt,
                    'total_amount' => ($order->customer->total_amount-(($order->selling_price-$order->discount)*$this->product_qty)),

                ]); */
            }
            $this->showToastr("success", 'Product retured successfully');
            return redirect()->route('order_list');
        }
    }

    public function viewDueData($id)
    {
        if ($this->storeUser == '1') {
            $this->viewDueOrderDetails = ProductOrderDetails::with('due_payments')->find($id);
        } else {
            $this->viewDueOrderDetails = ProductOrderDetails2::with('due_payments')->find($id);
        }
        $this->state['id'] = $this->viewDueOrderDetails->id;
        $this->state['total_amount'] = $this->viewDueOrderDetails->total_amount;
        $this->state['due_amount'] = $this->viewDueOrderDetails->due_amount;
        $this->state['collected_amount'] = $this->viewDueOrderDetails->collected_amount;
        $this->state['pay_amount'] = null;
        $this->dispatchBrowserEvent('view-due-amount');
    }
    public function saveDueAmount()
    {
        if ($this->storeUser == 1) {
            $orderData = ProductOrderDetails::find($this->state['id']);
        } else {

            $orderData = ProductOrderDetails2::find($this->state['id']);
        }


        Validator::make($this->state, [
            'pay_amount' => 'required|numeric|max:' . $orderData->due_amount,
        ])->validate();

        $current_due = 0;
        if ($orderData->due_amount != (float)$this->state['pay_amount']) {
            $current_due = $orderData->due_amount - (float)$this->state['pay_amount'];
        }

        if ($this->storeUser == 1) {
            $data = OrderDueAmount::create([
                'order_id' => $orderData->id,
                'total_amount'  => $orderData->total_amount,
                'due_amount'  => $current_due,
                'collected_amount'  => $this->state['pay_amount'],
                'order_date'  => date('Y-m-d'),
                'date'  => date('Y-m-d'),
            ]);
        } else {
            $data = OrderDueAmount2::create([
                'order_id' => $orderData->id,
                'total_amount'  => $orderData->total_amount,
                'due_amount'  => $current_due,
                'collected_amount'  => $this->state['pay_amount'],
                'order_date'  => date('Y-m-d'),
                'date'  => date('Y-m-d'),
            ]);
        }

        $orderData->update([
            'due_amount'  => $current_due,
            'collected_amount'  => ($orderData->collected_amount + (float)$this->state['pay_amount']),
        ]);

        $this->showToastr("success", 'Payment updated successfully');
        return redirect()->route('order_list');
    }

    public function returnOrder($id)
    {
        if ($this->storeUser == 1)
            $this->returnOrder = ProductOrder::with('customer')->find($id);
        else
            $this->returnOrder = ProductOrder2::with('customer')->find($id);

        $this->return_order_id = $this->returnOrder->id;
        $this->product_name = $this->returnOrder->product_name;
        $this->product_code = $this->returnOrder->product_code;
        $this->product_qty = $this->returnOrder->qty;
        $this->product_selling_price = $this->returnOrder->selling_price;

        $this->dispatchBrowserEvent('show-return-product-form');
    }


    public function render()
    {

        if ($this->storeUser == '1') {
            $purchaseReportQuery = ModelPurchaseReport::query();
            $orderQuery = ProductOrderDetails::with('productDetails', 'user')->withCount('due_payments');
            $orderQuery3 = ProductOrderDetails::query();
            $orderQuery4 = ProductOrderDetails::query();
             $totalOrderQuery = ProductOrderDetails::select(DB::raw("sum(st_product_order_details.total_amount) as total_sales"),  DB::raw("sum(st_product_order_details.subtotal) as total_product_selling_price"), DB::raw("sum(cast(st_product_order_details.due_amount as decimal(10,2))) as 'total_due_amount'"), DB::raw("sum(st_product_order_details.collected_amount) as 'total_collected_amount'"));
            $totalOrderQuery2 = ProductOrder::join('st_product_order_details', 'st_product_order.order_id', '=', 'st_product_order_details.order_id')
                ->select(DB::raw('CAST(sum(st_product_order.qty*st_product_order.selling_price)-sum(st_product_order.qty*st_product_order.purchase_price)  AS FLOAT) as total_profit'), 'st_product_order_details.order_date as order_date', DB::raw("sum(st_product_order.qty*st_product_order.discount) as 'total_discount_amount'"));
        } else {
            $purchaseReportQuery = ModelPurchaseReport2::query();
            $orderQuery = ProductOrderDetails2::with('productDetails', 'user')->withCount('due_payments');
            $orderQuery3 = ProductOrderDetails2::query();
            $orderQuery4 = ProductOrderDetails2::query();
            $totalOrderQuery = ProductOrderDetails2::select(DB::raw("sum(st_product_order_details.total_amount) as total_sales"),  DB::raw("sum(st_product_order_details.subtotal) as total_product_selling_price"), DB::raw("sum(cast(st_product_order_details.due_amount as decimal(10,2))) as 'total_due_amount'"), DB::raw("sum(st_product_order_details.collected_amount) as 'total_collected_amount'"));
            $totalOrderQuery2 = ProductOrder2::join('st_product_order_details', 'st_product_order.order_id', '=', 'st_product_order_details.order_id')
                ->select(DB::raw('CAST(sum(st_product_order.qty*st_product_order.selling_price)-sum(st_product_order.qty*st_product_order.purchase_price)  AS FLOAT) as total_profit'), 'st_product_order_details.order_date as order_date', DB::raw("sum(st_product_order.qty*st_product_order.discount) as 'total_discount_amount'"));
        }
        if ($this->searchName) {
            $name = $this->searchName;
            $orderQuery->where(function ($q) use ($name) {
                $q->where('customer_name', 'like', '%' . $name . '%')->orWhere('customer_phone', 'like', '%' . $name . '%')->orWhere('st_product_order_details.order_id', 'like', '%' . $name . '%');
            });
            $orderQuery3->where(function ($q) use ($name) {
                $q->where('customer_name', 'like', '%' . $name . '%')->orWhere('customer_phone', 'like', '%' . $name . '%')->orWhere('st_product_order_details.order_id', 'like', '%' . $name . '%');
            });
            $orderQuery4->where(function ($q) use ($name) {
                $q->where('customer_name', 'like', '%' . $name . '%')->orWhere('customer_phone', 'like', '%' . $name . '%')->orWhere('st_product_order_details.order_id', 'like', '%' . $name . '%');
            });
            $totalOrderQuery->where(function ($q) use ($name) {
                $q->where('customer_name', 'like', '%' . $name . '%')->orWhere('customer_phone', 'like', '%' . $name . '%')->orWhere('st_product_order_details.order_id', 'like', '%' . $name . '%');
            });
            $totalOrderQuery2->where(function ($q) use ($name) {
                $q->where('customer_name', 'like', '%' . $name . '%')->orWhere('customer_phone', 'like', '%' . $name . '%')->orWhere('st_product_order_details.order_id', 'like', '%' . $name . '%');
            });
        } else {
            // dd('ok');
            if ($this->dateForm) {
                $date['form_date'] = $this->dateForm;
                $orderQuery = $orderQuery->where(DB::raw("DATE(order_date)"), '>=', date('Y-m-d', strtotime($date['form_date'])));
                $orderQuery3 = $orderQuery3->where(DB::raw("DATE(order_date)"), '>=', date('Y-m-d', strtotime($date['form_date'])));
                $orderQuery4 = $orderQuery4->where(DB::raw("DATE(order_date)"), '>=', date('Y-m-d', strtotime($date['form_date'])));
                $totalOrderQuery = $totalOrderQuery->where(DB::raw("DATE(order_date)"), '>=', date('Y-m-d', strtotime($date['form_date'])));
                $totalOrderQuery2 = $totalOrderQuery2->where(DB::raw("DATE(order_date)"), '>=', date('Y-m-d', strtotime($date['form_date'])));
                 $purchaseReportQuery = $purchaseReportQuery->where(DB::raw("DATE(date)"),'>=',date('Y-m-d',strtotime($this->dateForm)));
            }
            if ($this->dateTo) {
                $date['to_date'] = $this->dateTo;
                $orderQuery = $orderQuery->where(DB::raw("DATE(order_date)"), '<=', date('Y-m-d', strtotime($date['to_date'])));
                $orderQuery3 = $orderQuery3->where(DB::raw("DATE(order_date)"), '<=', date('Y-m-d', strtotime($date['to_date'])));
                $orderQuery4 = $orderQuery4->where(DB::raw("DATE(order_date)"), '<=', date('Y-m-d', strtotime($date['to_date'])));
                $totalOrderQuery = $totalOrderQuery->where(DB::raw("DATE(order_date)"), '<=', date('Y-m-d', strtotime($date['to_date'])));
                $totalOrderQuery2 = $totalOrderQuery2->where(DB::raw("DATE(order_date)"), '<=', date('Y-m-d', strtotime($date['to_date'])));
                $purchaseReportQuery = $purchaseReportQuery->where(DB::raw("DATE(date)"),'<=',date('Y-m-d',strtotime($this->dateTo)));
            }
        }
         $total_expense = $purchaseReportQuery->sum('purchase_price');
         $total_cash_amount = $orderQuery3->whereIn('payment_mode', [1])->sum('collected_amount');
         $total_online_amount = $orderQuery4->whereIn('payment_mode', [2,3])->sum('collected_amount');
        $sales = $totalOrderQuery->first();
        $sales2 = $totalOrderQuery2->first();
        /*    dump($sales2);
    dd($sales); */
        $orders = $orderQuery
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
        return view('livewire.admin.order.order-list', [
            'orders' => $orders,
            'sales' => $sales,
            'sales2' => $sales2,
            'total_expense' => $total_expense,
            'total_cash_amount' => $total_cash_amount,
            'total_online_amount' => $total_online_amount,
        ]);
    }
    public function deleteAttempt($id)
    {
        $this->showConfirmation("warning", 'Are you sure?', "You won't be able to recover this Order!", 'Yes, delete!', 'deleteConfirm', ['id' => $id]);
    }


    public function deleteConfirm($id)
    {
        if ($this->storeUser == 1)
            $deleteOrder = ProductOrderDetails::with('productDetails')->find($id['id']);
        else
            $deleteOrder = ProductOrderDetails2::with('productDetails')->find($id['id']);

        if (count($deleteOrder->productDetails)) {
            foreach ($deleteOrder->productDetails as $key => $value) {
                $product = Product::find($value->product_id);
                if ($product) {
                    $product->update([
                        'quantity' => ($product->quantity + $value->qty),
                    ]);
                }
                $value->delete();
            }
        }
        if (count($deleteOrder->cards)) {
            foreach ($deleteOrder->cards as $key => $value) {
                $value->delete();
            }
        }
        $deleteOrder->delete();
        $this->showModal('success', 'Success', 'Order has been deleted successfully');
    }
}
