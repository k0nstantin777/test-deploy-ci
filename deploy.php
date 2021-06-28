<?php
namespace Deployer;

require 'recipe/laravel.php';
require 'contrib/php-fpm.php';
require 'contrib/rabbit.php';

set('application', 'Test Deploy CI');
set('repository', 'git@github.com:k0nstantin777/test-deploy-ci.git');
set('php_fpm_version', '8.0');
set('git_tty', true);

set('rabbit', [
    'host'     => env('RABBITMQ_HOST', '127.0.0.1'),
    'port'     => env('RABBITMQ_PORT', 5672),
    'username' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),
]);

 host('test-deploy')
    ->set('remote_user', 'deploy')
    ->set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader')
    ->set('hostname', 'test-deploy.knoskov.ru')
    ->set('deploy_path', '/var/www/{{hostname}}');

 task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:migrate',
    'testdb:create',
    'deploy:publish',
    'deploy:success'
]);

task('rollback', [
    'artisan:migrate:rollback',
]);

task('testdb:create', function () {
    cd('{{release_or_current_path}}');
    run('touch database/test.sqlite');
});

before('deploy:success', 'deploy:rabbit');

after('deploy:failed', 'deploy:unlock');

after('deploy', 'php-fpm:reload');

after('rollback', 'php-fpm:reload');