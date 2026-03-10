<?php

test('the homepage redirects to the dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});
