<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    if ($response->status() === 500) {
        dd($response->content(), $response->headers->all());
    }

    $response->assertStatus(200);
});
