import { Application } from '@hotwired/stimulus';
import { describe, expect, it } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

describe('datatable_controller setup', () => {
    it('can be registered as a Stimulus controller', async () => {
        document.body.innerHTML = `
            <div
                data-controller="zhortein--datatable-bundle--datatable"
                data-zhortein--datatable-bundle--datatable-name-value="users"
                data-zhortein--datatable-bundle--datatable-fragments-url-value="/_zhortein/datatable/users/fragments"
                data-zhortein--datatable-bundle--datatable-auto-load-value="false"
            ></div>
        `;

        const application = Application.start();
        application.register('zhortein--datatable-bundle--datatable', DatatableController);

        await Promise.resolve();

        const controller = application.getControllerForElementAndIdentifier(
            document.querySelector('[data-controller="zhortein--datatable-bundle--datatable"]'),
            'zhortein--datatable-bundle--datatable'
        );

        expect(controller).toBeInstanceOf(DatatableController);

        application.stop();
    });
});
