#!/usr/bin/env bash

set -euo pipefail

bundle_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
smoke_root="$(mktemp -d)"
app_root="${smoke_root}/app"

cleanup() {
    rm -rf "${smoke_root}"
}

trap cleanup EXIT

composer create-project \
    symfony/skeleton:"8.0.*" \
    "${app_root}" \
    --no-interaction \
    --no-progress

cd "${app_root}"

repository_config="$(
    php -r '
        echo json_encode([
            "type" => "path",
            "url" => $argv[1],
            "options" => [
                "symlink" => false,
                "versions" => [
                    "zhortein/datatable-bundle" => "1.0.x-dev",
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    ' "${bundle_root}"
)"

composer config repositories.zhortein-datatable "${repository_config}"
composer require \
    zhortein/datatable-bundle:1.0.x-dev \
    symfony/asset \
    symfony/asset-mapper \
    symfony/stimulus-bundle \
    symfony/twig-bundle \
    --no-interaction \
    --no-progress

php -r '
    $path = "config/bundles.php";
    $bundles = require $path;
    $bundles[\Zhortein\DatatableBundle\ZhorteinDatatableBundle::class] = ["all" => true];
    file_put_contents(
        $path,
        "<?php\n\nreturn ".var_export($bundles, true).";\n",
    );
'

install -D \
    "${bundle_root}/tools/smoke-test/fixtures/zhortein_datatable.yaml" \
    config/routes/zhortein_datatable.yaml
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/controllers.json" \
    assets/controllers.json
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/app.js" \
    assets/app.js
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/SmokeUserDatatable.php" \
    src/Datatable/SmokeUserDatatable.php
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/SmokeController.php" \
    src/Controller/SmokeController.php
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/smoke.yaml" \
    config/routes/smoke.yaml
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/smoke.html.twig" \
    templates/smoke.html.twig
install \
    "${bundle_root}/tools/smoke-test/fixtures/smoke.php" \
    smoke.php

test -f assets/stimulus_bootstrap.js

php bin/console importmap:require bootstrap --no-interaction
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css --no-interaction

php bin/console debug:router zhortein_datatable_fragments --format=json
php bin/console debug:router zhortein_datatable_export --format=json

php bin/console debug:container 'App\Datatable\SmokeUserDatatable'
php bin/console debug:asset-map '@zhortein/datatable-bundle'

php bin/console asset-map:compile
php smoke.php
