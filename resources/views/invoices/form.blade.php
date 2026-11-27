@extends('layouts.app')

@section('heading', $title)

@section('content')
    <div class="max-w-5xl">
        <h1 class="mb-5 text-2xl font-bold">{{ $title }}</h1>
        <div class="card">
            <form method="POST" action="{{ $action }}" class="space-y-5">
                @csrf
                @if($method === 'PUT') @method('PUT') @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        Client *
                        <select class="field mt-1" name="client_id" required>
                            <option value="">Select client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $invoice->client_id ?? $selectedClientId) === (string) $client->id)>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">
                        Case
                        <select class="field mt-1" name="case_id">
                            <option value="">No case linked</option>
                            @foreach($cases as $case)
                                <option value="{{ $case->id }}" @selected((string) old('case_id', $invoice->case_id ?? $selectedCaseId) === (string) $case->id)>
                                    {{ $case->case_number }} - {{ $case->title }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block text-sm">
                        Issue date *
                        <input class="field mt-1" type="date" name="issue_date" value="{{ old('issue_date', $invoice->issue_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                    </label>
                    <label class="block text-sm">
                        Due date
                        <input class="field mt-1" type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                    </label>
                    <label class="block text-sm">
                        Currency *
                        <input class="field mt-1 uppercase" name="currency" value="{{ old('currency', $invoice->currency ?: 'USD') }}" maxlength="3" required>
                    </label>
                </div>

                <label class="block text-sm">
                    Status *
                    <select class="field mt-1" name="status" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $invoice->status ?: 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div>
                    <h2 class="mb-2 font-bold">Fee items</h2>
                    <div class="overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit price</th>
                                    <th>Tax %</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items">
                                @php
                                    $oldItems = old('items');
                                    $items = $oldItems ?? ($invoice->exists ? $invoice->items->map(fn ($item) => [
                                        'description' => $item->description,
                                        'quantity' => $item->quantity,
                                        'unit_price' => $item->unit_price,
                                        'tax_percent' => $item->tax_percent,
                                    ]) : [['description' => '', 'quantity' => '1', 'unit_price' => '', 'tax_percent' => '0']]);
                                @endphp
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td><input class="field min-w-52" name="items[{{ $index }}][description]" value="{{ $item['description'] }}" maxlength="255" required></td>
                                        <td><input class="field min-w-24" type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}" min="0.01" step="0.01" required></td>
                                        <td><input class="field min-w-28" type="number" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] }}" min="0" step="0.01" required></td>
                                        <td><input class="field min-w-24" type="number" name="items[{{ $index }}][tax_percent]" value="{{ $item['tax_percent'] }}" min="0" max="100" step="0.01" required></td>
                                        <td><button class="remove-item text-red-600" type="button">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button id="add-item" class="btn mt-3 border border-slate-300 bg-white" type="button">Add fee item</button>
                </div>

                <label class="block text-sm">
                    Notes
                    <textarea class="field mt-1" name="notes" rows="4" maxlength="5000">{{ old('notes', $invoice->notes) }}</textarea>
                </label>

                <button class="btn primary" type="submit">Save invoice</button>
            </form>
        </div>
    </div>

    <template id="invoice-item-template">
        <tr>
            <td><input class="field min-w-52" name="items[INDEX][description]" maxlength="255" required></td>
            <td><input class="field min-w-24" type="number" name="items[INDEX][quantity]" value="1" min="0.01" step="0.01" required></td>
            <td><input class="field min-w-28" type="number" name="items[INDEX][unit_price]" min="0" step="0.01" required></td>
            <td><input class="field min-w-24" type="number" name="items[INDEX][tax_percent]" value="0" min="0" max="100" step="0.01" required></td>
            <td><button class="remove-item text-red-600" type="button">Remove</button></td>
        </tr>
    </template>
    <script>
        const itemBody = document.getElementById('invoice-items');
        const template = document.getElementById('invoice-item-template');
        document.getElementById('add-item').addEventListener('click', () => {
            const index = itemBody.children.length;
            itemBody.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('INDEX', index));
        });
        itemBody.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-item') && itemBody.children.length > 1) {
                event.target.closest('tr').remove();
            }
        });
    </script>
@endsection
