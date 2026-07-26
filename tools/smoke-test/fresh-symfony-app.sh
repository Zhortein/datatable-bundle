#!/usr/bin/env bash

set -euo pipefail

bundle_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
smoke_root="$(mktemp -d)"
app_root="${smoke_root}/app"
server_pid=""
bundle_version="${SMOKE_BUNDLE_VERSION:-current}"

cleanup() {
    if [[ -n "${server_pid}" ]]; then
        kill "${server_pid}" 2>/dev/null || true
    fi

    rm -rf "${smoke_root}"
}

trap cleanup EXIT

composer create-project \
    symfony/skeleton:"8.0.*" \
    "${app_root}" \
    --no-interaction \
    --no-progress

cd "${app_root}"

install -D \
    "${bundle_root}/tools/smoke-test/fixtures/SmokeController.php" \
    src/Controller/SmokeController.php
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/smoke.yaml" \
    config/routes/smoke.yaml
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/smoke.html.twig" \
    templates/smoke.html.twig
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/zhortein_datatable.yaml" \
    config/routes/zhortein_datatable.yaml
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/SmokeUserDatatable.php" \
    src/Datatable/SmokeUserDatatable.php

bundle_constraint="${bundle_version}"

if [[ "${bundle_version}" == "current" ]]; then
    repository_config="$(
        php -r '
            echo json_encode([
                "type" => "path",
                "url" => $argv[1],
                "options" => [
                    "symlink" => false,
                    "versions" => [
                        "zhortein/datatable-bundle" => "1.1.x-dev",
                    ],
                ],
            ], JSON_THROW_ON_ERROR);
        ' "${bundle_root}"
    )"

    composer config repositories.zhortein-datatable "${repository_config}"
    bundle_constraint="1.1.x-dev"
fi

composer require \
    "zhortein/datatable-bundle:${bundle_constraint}" \
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
    "${bundle_root}/tools/smoke-test/fixtures/controllers.json" \
    assets/controllers.json
install -D \
    "${bundle_root}/tools/smoke-test/fixtures/app.js" \
    assets/app.js
install \
    "${bundle_root}/tools/smoke-test/fixtures/smoke.php" \
    smoke.php
install \
    "${bundle_root}/tools/smoke-test/fixtures/configuration.php" \
    configuration.php

install -D \
    "${bundle_root}/tools/smoke-test/fixtures/zhortein_datatable_minimal.yaml" \
    config/packages/zhortein_datatable.yaml

php bin/console cache:clear
minimal_config_dump="${smoke_root}/minimal-config.txt"
php bin/console debug:config zhortein_datatable >"${minimal_config_dump}"
php configuration.php minimal "${minimal_config_dump}"

install \
    "${bundle_root}/tools/smoke-test/fixtures/zhortein_datatable_complete.yaml" \
    config/packages/zhortein_datatable.yaml

php bin/console cache:clear
php bin/console cache:warmup
complete_config_dump="${smoke_root}/complete-config.txt"
php bin/console debug:config zhortein_datatable >"${complete_config_dump}"
php configuration.php complete "${complete_config_dump}"

test -f assets/stimulus_bootstrap.js

php bin/console importmap:require bootstrap --no-interaction
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css --no-interaction

php bin/console debug:router app_smoke --format=json
php bin/console debug:router zhortein_datatable_fragments --format=json
php bin/console debug:router zhortein_datatable_export --format=json

php bin/console debug:container 'App\Datatable\SmokeUserDatatable'
php bin/console debug:asset-map '@zhortein/datatable-bundle'

php bin/console asset-map:compile

server_log="${smoke_root}/server.log"
shell_response="${smoke_root}/shell.html"
fragments_response="${smoke_root}/fragments.json"
csv_response="${smoke_root}/export.csv"
base_url="http://127.0.0.1:8000"

APP_ENV=dev APP_DEBUG=1 php -S 127.0.0.1:8000 -t public public/index.php \
    >"${server_log}" 2>&1 &
server_pid="$!"

if ! curl \
    --fail \
    --retry 10 \
    --retry-connrefused \
    --retry-delay 1 \
    --show-error \
    --silent \
    "${base_url}/smoke" \
    --output "${shell_response}"; then
    cat "${server_log}"
    exit 1
fi

if ! curl \
    --fail \
    --show-error \
    --silent \
    "${base_url}/_zhortein/datatable/smoke-users/fragments" \
    --output "${fragments_response}"; then
    cat "${server_log}"
    exit 1
fi

if ! curl \
    --fail \
    --show-error \
    --silent \
    "${base_url}/_zhortein/datatable/smoke-users/export/csv" \
    --output "${csv_response}"; then
    cat "${server_log}"
    exit 1
fi

php smoke.php "${shell_response}" "${fragments_response}" "${csv_response}"
