<?php

it('includes the deployer scaffold for production deployments', function () {
    $projectRoot = dirname(__DIR__, 2);

    $composer = json_decode(
        file_get_contents($projectRoot.'/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $deployConfig = file_get_contents($projectRoot.'/deploy.php');
    $envExample = file_get_contents($projectRoot.'/.env.example');
    $workflow = file_get_contents($projectRoot.'/.github/workflows/deploy-production.yml');

    expect($composer['scripts'])
        ->toHaveKey('deploy')
        ->and($composer['scripts']['deploy'])
        ->toBe(['dep deploy']);

    expect($deployConfig)
        ->toContain("require 'recipe/laravel.php';")
        ->toContain("getenv('DEPLOY_HOSTNAME')")
        ->toContain("getenv('DEPLOY_HTTP_USER')")
        ->toContain("getenv('DEPLOY_REMOTE_USER')")
        ->toContain("set('writable_mode', 'acl');")
        ->toContain("task('build:assets'")
        ->toContain("task('upload:assets'")
        ->toContain("task('queue:restart'")
        ->toContain("before('deploy', 'build:assets');")
        ->toContain("after('deploy:vendors', 'upload:assets');")
        ->toContain("after('deploy:symlink', 'queue:restart');");

    expect($envExample)
        ->toContain('DEPLOY_HOSTNAME=')
        ->toContain('DEPLOY_PATH=')
        ->toContain('DEPLOY_SSH_PORT=22')
        ->toContain('DEPLOY_REPOSITORY=')
        ->toContain('DEPLOY_REMOTE_USER=')
        ->toContain('DEPLOY_HTTP_USER=www-data');

    expect($workflow)
        ->toContain('name: Deploy to Production')
        ->toContain('branches: [main]')
        ->toContain('uses: deployphp/action@v1')
        ->toContain('private-key: ${{ secrets.SSH_PRIVATE_KEY }}')
        ->toContain('DEPLOY_HOSTNAME: ${{ vars.DEPLOY_HOSTNAME }}')
        ->toContain('DEPLOY_PATH: ${{ vars.DEPLOY_PATH }}')
        ->toContain('DEPLOY_SSH_PORT: ${{ vars.DEPLOY_SSH_PORT }}')
        ->toContain('DEPLOY_REMOTE_USER: ${{ vars.DEPLOY_REMOTE_USER }}')
        ->toContain('DEPLOY_HTTP_USER: ${{ vars.DEPLOY_HTTP_USER }}')
        ->toContain('DEPLOY_REPOSITORY: ${{ github.server_url }}/${{ github.repository }}.git');
});
