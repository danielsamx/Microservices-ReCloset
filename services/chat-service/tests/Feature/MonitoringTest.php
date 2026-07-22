<?php
namespace Tests\Feature;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    /** El endpoint de métricas responde 200 y expone el formato Prometheus. */
    public function test_metrics_endpoint_expone_prometheus(): void
    {
        $res = $this->get('/metrics');
        $res->assertStatus(200);
        $res->assertSee('recloset_up', false);
        $res->assertSee('recloset_http_requests_total', false);
    }

    /** El endpoint de salud responde JSON con la clave status. */
    public function test_health_endpoint_devuelve_status(): void
    {
        $res = $this->get('/health');
        $res->assertJsonStructure(['status', 'service', 'time']);
    }
}
