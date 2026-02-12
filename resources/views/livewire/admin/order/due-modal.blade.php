<div wire:ignore.self id="viewDueAmountModal" data-backdrop="static" data-keyboard="false" class="modal"
        tabindex="-1" role="dialog" aria-labelledby="return-modalLabel" aria-hidden="true"
        style="z-index: 999999;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-colored-header bg-primary">
                    <h4 class="modal-title" id="primary-header-modalLabel">Due Amount</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-centered w-100 dt-responsive nowrap"
                        id="products-datatable">
                        <thead class="table-light">

                            <tr>
                                <th>Customer Name</th>
                                <th>Customer Phone</th>
                                <th>Total Amount</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>


                            <tr>
                                <td>{{ @$viewDueOrderDetails->customer_name }}</td>
                                <td>{{ @$viewDueOrderDetails->customer_phone }}</td>
                                <td>{{ env('CURRENCY', '₹') }}{{ @$viewDueOrderDetails->total_amount }}</td>
                                <td>{{ date('d/m/Y', strtotime(@$viewDueOrderDetails->order_date)) }}</td>
                            </tr>


                        </tbody>
                    </table>
                    <span class="badge badge-outline-danger p-1 font-16 mb-1 ms-2">Order Id -
                        {{ @$viewDueOrderDetails->order_id }}</span>
                </div>

                @if (isset($viewDueOrderDetails) && count(@$viewDueOrderDetails->due_payments))
                    <div class="table-responsive">
                        <table class="table table-bordered table-centered w-100 dt-responsive nowrap"
                            id="products-datatable">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="4" style="background-color: #009CEC;">Due Payment History
                                    </th>
                                </tr>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Total Amount</th>
                                    <th>Collected Amount</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($viewDueOrderDetails->due_payments as $details)
                                    <tr>
                                        <td>{{ date('d/m/Y', strtotime(@$details->date)) }}</td>
                                        <td>{{ env('CURRENCY', '₹') }}{{ @$details->total_amount }}</td>
                                        <td>{{ env('CURRENCY', '₹') }}{{ @$details->collected_amount }}</td>
                                        <td>{{ env('CURRENCY', '₹') }}{{ @$details->due_amount }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td>{{ env('CURRENCY', '₹') }}{{ @convert_numbers_to_indian_format($viewDueOrderDetails->collected_amount, 2) }}
                                    </td>
                                    <td>{{ env('CURRENCY', '₹') }}{{ @convert_numbers_to_indian_format($viewDueOrderDetails->due_amount, 2) }}
                                    </td>
                                </tr>


                            </tbody>
                        </table>
                    </div>
                @endif
                @if (@$viewDueOrderDetails->due_amount > 0)
                    <form wire:submit.prevent="saveDueAmount">
                        <input type="hidden" wire:model.defer="state.id">
                        <div class="modal-body">

                            <div class="row mt-2">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Total Amount</label>
                                    <input type="text" class="form-control" placeholder="Total Amount"
                                        wire:model.defer="state.total_amount" readonly>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Collected Amount</label>
                                    <input type="text" class="form-control" placeholder="Collected Amount"
                                        wire:model.defer="state.collected_amount" readonly>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Current Due Amount</label>
                                    <input type="text" class="form-control" placeholder="Current Due Amount"
                                        wire:model.defer="state.due_amount" readonly>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Pay Amount</label>
                                    <input type="text" class="form-control from-amount" placeholder="Pay Amount"
                                        wire:model.defer="state.pay_amount"
                                        onkeypress="return decimal_number_check(event);">
                                    @error('pay_amount')
                                        <span class="text-danger error">{{ $message }}</span>
                                    @enderror
                                </div>


                            </div>
                        </div>


                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                @endif



            </div>
        </div>
    </div>