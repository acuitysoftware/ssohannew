<?php

namespace App\Http\Livewire\Admin\ProductReport;

use DB;
use Auth;
use Session;
use App\Models\User;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Gallery;
use App\Models\ProductQuantity;
use App\Models\PurchaseReport as ModelPurchaseReport;
use App\Models\PurchaseReport2 as ModelPurchaseReport2;
use App\Models\ProductOrder;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Http\Livewire\Traits\WithSorting;
use App\Http\Livewire\Traits\AlertMessage;

class PurchaseReport extends Component
{
    use WithPagination;
    use AlertMessage;
	public $perPage, $orderList=[],$product, $dateForm, $dateTo, $note, $date, $purchase_price, $total_price,$edit_note, $edit_date, $edit_purchase_price, $report_id, $user_id, $storeUser;
    protected $paginationTheme = 'bootstrap';
	protected $listeners = ['deleteConfirm', 'changeStatus','deleteConfirmUsers','loadMore'];

	public function mount()
	{
		//$this->perPage =1; 
		$this->perPage =env('PER_PAGE', 50); 
        $this->date=date('Y-m-d');
        $this->user_id=auth()->user()->id;
        if(Auth::user()->type=='A')
        {
            $this->storeUser = 1;
            $store = Session::get('store');
            if($store)
            {
                $this->storeUser = $store;
            }
        }
        else{

            $this->storeUser = Auth::user()->store;
        }
	}

	public function loadMore()
    {
        $this->perPage= $this->perPage+env('PER_PAGE', 50);
    }

    public function resetSearch()
    {
       $this->dateForm = null;
       $this->dateTo = null;
    }

    public function save()
    {
    	$data = $this->validate([
    		'note' => 'required',
    		'date' => 'required',
    		'user_id' => 'required',
    		'purchase_price' => 'required|numeric',
    	]);
        if($this->storeUser == 1)
    	    ModelPurchaseReport::create($data);
        else
    	    ModelPurchaseReport2::create($data);

        return redirect()->route('purchase_report');
    }

    public function render()
    {
        if($this->storeUser == 1){

        
            $purchaseReportQuery = ModelPurchaseReport::query();
        }
        else{

            $purchaseReportQuery = ModelPurchaseReport2::query();
        }
        if(Auth::user()->type !='A'){
            $purchaseReportQuery->where('user_id', auth()->user()->id);
        }
    	
        if($this->dateForm)
        {
            //$date['form_date'] = $this->dateForm;
            $purchaseReportQuery = $purchaseReportQuery->where(DB::raw("DATE(date)"),'>=',date('Y-m-d',strtotime($this->dateForm)));
        }
        if($this->dateTo)
        {
            //$date['to_date'] = $this->dateTo;
            $purchaseReportQuery = $purchaseReportQuery->where(DB::raw("DATE(date)"),'<=',date('Y-m-d',strtotime($this->dateTo)));
        }
        $total_purchase_price = $purchaseReportQuery->sum('purchase_price');
        $reports = $purchaseReportQuery->orderBy('id', 'desc')->paginate($this->perPage);
        return view('livewire.admin.product-report.purchase-report', [
            'reports' => $reports,
            'total_purchase_price' => $total_purchase_price
        ]);
    }

    public function edit($id)
    {
        if($this->storeUser == 1)
            $data = ModelPurchaseReport::find($id);
        else
            $data = ModelPurchaseReport2::find($id);

    	$this->report_id = $data->id;
    	$this->edit_note = $data->note;
    	$this->edit_date = $data->date;
    	$this->edit_purchase_price = $data->purchase_price;
    	$this->dispatchBrowserEvent('show-edit-purchase-report');

    }
    public function updateReport()
    {
    	$data = $this->validate([
    		'edit_note' => 'required',
    		'edit_date' => 'required',
    		'edit_purchase_price' => 'required|numeric',
    	]);
        if($this->storeUser == 1)
            $report = ModelPurchaseReport::find($this->report_id);
        else
            $report = ModelPurchaseReport2::find($this->report_id);
        
    	$report->update([
    		'note' => $this->edit_note,
    		'date' => $this->edit_date,
    		'purchase_price' => $this->edit_purchase_price,
    	]);
        return redirect()->route('purchase_report')->with('success', 'Data update successfully');
    }
    public function deleteAttempt($id)
    {
        $this->showConfirmation("warning", 'Are you sure?', "You won't be able to recover this Order!", 'Yes, delete!', 'deleteConfirm', ['id' => $id]);
    }

    public function deleteConfirm($id)
    {
        if($this->storeUser == 1)
            $deleteOrder = ModelPurchaseReport::find($id['id']);
        else
            $deleteOrder = ModelPurchaseReport::find($id['id']);
        
        $deleteOrder->delete();
        $this->showModal('success', 'Success', 'Data been deleted successfully');
    }
}
