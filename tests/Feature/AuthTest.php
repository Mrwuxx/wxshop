<?php


namespace Tests\Feature;


use App\Services\UserServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    //事务回滚，防止重复提交
    use DatabaseTransactions;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testRegister()
    {
        $code = (new UserServices())->setCaptcha('13111111111');
        $response = $this->post('wx/auth/register',[
            'username' => 'mrwu',
            'password' => '123456',
            'mobile' => '18051823439',
            'code' => $code
        ]);
        echo $response->getContent();
        /*$response->assertJson(200);
        $ret = $response->getOriginalContent();
        $this->assertEquals(0,$ret['errno']);
        $this->assertNotEmpty($ret['data']);*/
    }

    public function testRegCaptcha()
    {
        $response = $this->post('auth/regCaptcha',['mobile'=>'13111111911']);
        $response->assertJson(['errno'=>0,'errmsg'=>'成功','data'=>null]);
    }
}
