@extends('frontend.pages.account.base')

@section('account_content')
    <div class="account-card">
        <div class="d-flex flex-wrap justify-content-between gap-3 account-card-title">
            <div>
                <h4 class="mb-1">طلبات بطاقات الهدايا</h4>
                <p class="text-muted mb-0">تابع حالة طلبات بطاقات الهدايا الخاصة بك.</p>
            </div>
            <a href="{{ route('front.gift-cards.create') }}" class="tf-btn btn-fill animate-hover-btn radius-3">
                طلب بطاقة جديدة
            </a>
        </div>

        <div class="account-table-wrap">
            <table class="account-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العدد</th>
                        <th>الإجمالي</th>
                        <th>حالة الطلب</th>
                        <th>حالة الدفع</th>
                        <th>البطاقات</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td dir="ltr">{{ $request->request_no }}</td>
                            <td>{{ (int) $request->card_quantity }}</td>
                            <td dir="ltr">{{ number_format((float) $request->total_amount, 0) }} {{ strtoupper((string) $request->currency) }}</td>
                            <td><span class="account-badge">{{ \App\Models\GiftCardRequest::statusOptions()[$request->status] ?? $request->status }}</span></td>
                            <td><span class="account-badge">{{ \App\Models\GiftCardRequest::paymentStatusOptions()[$request->payment_status] ?? $request->payment_status }}</span></td>
                            <td>{{ (int) $request->gift_cards_count }}</td>
                            <td>
                                <a href="{{ route('front.account.gift-card-requests.show', ['giftCardRequest' => $request->request_no]) }}" class="link">
                                    عرض
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                لا توجد طلبات بطاقات هدايا حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt_24">
            {{ $requests->links() }}
        </div>
    </div>
@endsection