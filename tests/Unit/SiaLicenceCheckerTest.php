<?php

namespace Tests\Unit;

use App\Services\SiaLicenceChecker;
use PHPUnit\Framework\TestCase;

class SiaLicenceCheckerTest extends TestCase
{
    public function test_it_parses_the_current_sia_result_card_markup(): void
    {
        $checker = new class extends SiaLicenceChecker {
            public function parseHtml(string $html, string $licenceNumber): array
            {
                return $this->parseResponse($html, $licenceNumber);
            }
        };

        $html = <<<'HTML'
<div class="container well panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3">
                <span class="ax_paragraph">First name</span>
                <div class="form-group"><div class="ax_h5">AAMAD</div></div>
            </div>
            <div class="col-md-3">
                <span class="ax_paragraph">Surname</span>
                <div class="form-group"><div class="ax_h5">AHMED</div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <span class="ax_paragraph">Licence number</span>
                <div class="form-group"><div class="ax_h4">1011001544208079</div></div>
            </div>
            <div class="col-md-3">
                <span class="ax_paragraph">Licence sector</span>
                <div class="form-group"><div class="ax_h4">Door Supervision</div></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <span class="ax_paragraph">Expiry date</span>
                <div class="form-group"><div class="ax_h4">06 June 2027</div></div>
            </div>
            <div class="col-md-3">
                <span class="ax_paragraph">Status</span>
                <div class="form-group"><span class="ax_h4_green">Active</span></div>
            </div>
        </div>
    </div>
</div>
HTML;

        $result = $checker->parseHtml($html, '1011001544208079');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['valid']);
        $this->assertSame('AAMAD AHMED', $result['holder_name']);
        $this->assertSame('Door Supervision', $result['licence_sector']);
        $this->assertSame('Active', $result['licence_status']);
        $this->assertSame('06 June 2027', $result['expiry_date']);
    }
}