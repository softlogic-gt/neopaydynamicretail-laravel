<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use SoftlogicGT\NeoPayDynamicRetail\Facades\NeoPayPos;
use SoftlogicGT\NeoPayDynamicRetail\Pos;
use SoftlogicGT\NeoPayDynamicRetail\PosClient;
use SoftlogicGT\NeoPayDynamicRetail\PosResponse;
use SoftlogicGT\NeoPayDynamicRetail\Tests\TestCase;

class PosFacadeTest extends TestCase
{
    public function test_facade_resolves_pos_singleton(): void
    {
        $this->assertSame(app(Pos::class), app(Pos::class));
        $this->assertInstanceOf(Pos::class, NeoPayPos::getFacadeRoot());
    }

    public function test_sale_returns_typed_response(): void
    {
        $this->fake('00', [
            'authIdResponse' => '022226',
            'retrievalRefNo' => '254555555555',
            'systemsTraceNo' => '000230',
            'voucher' => ['commerceVoucher' => 'COMERCIO', 'clientVoucher' => 'CLIENTE'],
        ]);

        $response = NeoPayPos::sale(230, 100);

        $this->assertInstanceOf(PosResponse::class, $response);
        $this->assertSame('022226', $response->authorization());
        $this->assertSame('254555555555', $response->retrievalRefNo());
        $this->assertSame('000230', $response->traceNumber());
        $this->assertSame('FONDOS INSUFICIENTES', $response->message());
        $this->assertSame('COMERCIO', $response->commerceVoucher());
        $this->assertSame('CLIENTE', $response->clientVoucher());
        $this->assertSame('default', $response->get('missing', 'default'));
        $this->assertSame('00', $response->toArray()['responseCode']);
    }

    public function test_every_operation_reaches_its_endpoint(): void
    {
        $this->fake();

        NeoPayPos::sale(1, 1);
        NeoPayPos::salePoints(2, 1);
        NeoPayPos::saleInstallments(3, 1, 3);
        NeoPayPos::saleExtraFinancing(4, 1, 18);
        NeoPayPos::saleDiscount(5, 2, 1);
        NeoPayPos::annulment(6);
        NeoPayPos::reverse(7, 1);
        NeoPayPos::checkIn(8, 1, 1);
        NeoPayPos::surcharge(9, 1, 1);
        NeoPayPos::checkOut(10, 1, 1);
        NeoPayPos::settlement();
        NeoPayPos::voucherReprint(11);
        NeoPayPos::sendDigitalVoucher('1');
        NeoPayPos::transactionDetail();
        NeoPayPos::settlementDetail();
        NeoPayPos::settlementBatch();
        NeoPayPos::reportTotals();

        $endpoints = array_map(fn ($r) => basename($r->url()), $this->posRequests());
        $this->assertSame(
            array_merge(
                array_fill(0, 10, 'paymentcommerceauthorization'),
                ['paymentcommercesettlement', 'authorizationvoucherreprint', 'SendDigitalVoucher', 'transactiondetail', 'settlementdetail', 'settlementbatch', 'reporttotals'],
            ),
            $endpoints,
        );
    }

    public function test_client_can_be_swapped_in_the_container(): void
    {
        $fake = new class extends PosClient {
            public array $calls = [];

            public function post(string $endpoint, array $body): PosResponse
            {
                $this->calls[] = $endpoint;

                return new PosResponse(['responseCode' => '00']);
            }
        };
        $this->app->instance(PosClient::class, $fake);

        NeoPayPos::annulment(1);

        $this->assertSame(['paymentcommerceauthorization'], $fake->calls);
    }
}
