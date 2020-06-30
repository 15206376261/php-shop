<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yansongda\Supports\Log;

class PaymentController extends Controller
{
    public function alipay(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        $order = [
            'out_trade_no' => $order->no,
            'total_amount' => $order->total_price,
            'subject' => '支付订单 ' . $order->no
        ];
        return app('alipay')->web($order);
    }

    /**
     * 支付宝 server 回调
     * @return string
     */
    public function alipayNotify()
    {
        try {
            $data = app('alipay')->verify();

            if (!in_array($data->trade_status, ['TRADE_FINISHED', 'TRADE_SUCCESS'])) {
                Log::error('finish or success error');
                return 'error';

            }
            if (!$order = Order::query()->where('no', $data->out_trade_no)->first()) {
                Log::error('order no not found', $data->all());
                return 'error';
            }


            if ($order->paid_at) {
                return app('alipay')->success();
            }
            $order->update([
                'payment_no' => $data->trade_no,
//                'paid_price' => $data->total_amount,
                'payment_method' => 'alipay',
                'paid_at' => Carbon::now(),
            ]);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return 'error';
        }
        return app('alipay')->success();
    }

    /**
     * 支付宝前端回调
     * @param Request $request
     */
    public function alipayReturn(Request $request)
    {
        $data = app('alipay')->verify();

        return view('common.success', [
            'data' => $data
        ]);
    }
}
