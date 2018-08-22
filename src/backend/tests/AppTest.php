<?php

class AppTest extends TestCase
{
    public function testUnauthorizedRequest()
    {
        $this->json('GET', '/api', [])
            ->assertResponseStatus(401);
        $this->json('POST', '/api', [])
            ->assertResponseStatus(401);
    }

    public function testAuthorizedRequest()
    {
        $headers = [
            'Authorization2' => 'Bearer superSecretToken',
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Basic ' . base64_encode('superSecretUser:superSecretPassword'),
        ];

        $this->json('GET', '/api', [], $headers)
            ->assertResponseStatus(200);
        $this->json('POST', '/api', [], $headers)
            ->assertResponseStatus(200);
    }


    public function testRequests()
    {

        $headers = [
            'Authorization2' => 'Bearer superSecretToken',
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Basic ' . base64_encode('superSecretUser:superSecretPassword'),
        ];

        $this->json('GET', '/api', [], $headers)
            ->seeJsonStructure(['date', 'count']);
        $this->json('POST', '/api', [], $headers)
            ->seeJsonStructure(['date', 'count']);
    }
}
