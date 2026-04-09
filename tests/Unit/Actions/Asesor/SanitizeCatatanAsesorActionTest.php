<?php

namespace Tests\Unit\Actions\Asesor;

use App\Actions\Asesor\SanitizeCatatanAsesorAction;
use PHPUnit\Framework\TestCase;

class SanitizeCatatanAsesorActionTest extends TestCase
{
    public function test_returns_null_for_null_or_empty_input(): void
    {
        $action = new SanitizeCatatanAsesorAction();

        $this->assertNull($action->execute(null));
        $this->assertNull($action->execute(''));
        $this->assertNull($action->execute('   '));
        $this->assertNull($action->execute('<p><br></p>'));
    }

    public function test_strips_disallowed_tags_and_keeps_allowed_structure(): void
    {
        $action = new SanitizeCatatanAsesorAction();

        $result = $action->execute('<p>Hello<script>alert(1)</script><img src=x onerror=1><strong>ok</strong></p>');

        $this->assertSame('<p>Hello<strong>ok</strong></p>', $result);
    }

    public function test_removes_attributes_from_allowed_tags(): void
    {
        $action = new SanitizeCatatanAsesorAction();

        $result = $action->execute('<p class="x">A <strong style="color:red">B</strong></p>');

        $this->assertSame('<p>A <strong>B</strong></p>', $result);
    }
}
