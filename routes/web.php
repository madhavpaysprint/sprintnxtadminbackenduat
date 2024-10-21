<?php
/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */
$router->get('/', function () use ($router) {
    return "Admin portal";
});
$router->post('getrefid', 'ExampleController@QrHit');
$router->post('cib-registration-status', 'BussinessBanking\PartnerRequests@getCibStatus');
$router->get('/get-files', 'FileController@getFileUrl');
$router->post('upload-normal-file','FileController@normal_file_upload');
$router->get('/display', 'FileController@display');
$router->group(['middleware' => 'checkip'], function () use ($router) {
    //auth routes
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('login', 'Auth\LoginController@index');
        $router->post('sendOtp', 'Auth\ResetController@sendOtp');
        $router->post('verifyOtp', 'Auth\ResetController@verifyOtp');
        $router->post('forgotPassword', 'Auth\ResetController@forgotPassword');
        $router->post('forgotPasswordEmail', 'Auth\ResetController@forgotPasswordMail');
        $router->group(['middleware' => ['auth:api']], function () use ($router) {
            $router->post('logout', 'Auth\LoginController@logout');
        });
        $router->group(['middleware' => ['auth:api']], function () use ($router) {
            $router->post('changePassword', ['as' => 'change-Password', 'uses' => 'Auth\ResetController@changePassword']);
            $router->post('user-permissions', 'Auth\LoginController@userPermissions');
            $router->post('leftPanel', ['as' => 'left-panel','uses' => 'Auth\LoginController@getLeftPanel']);

        });
    });
    $router->post('search-merchant', ['as' => 'search-merchant', 'uses' => 'User\UserController@searchUser']);
    $router->group(['middleware' => ['auth:api']], function () use ($router) {
        $router->post('pmlist', 'Master\AdminConfigController@adminParentMenu');
        $router->post('admin-parent-menu', ['as' => 'admin-parent-menu', 'uses' => 'Master\AdminConfigController@adminParentMenu']);
        $router->post('front-parent-menu', ['as' => 'front-parent-menu', 'uses' => 'Master\FrontSidebarController@frontParentMenu']);
    });
    $router->group(['middleware' => ['auth:api','checkperm']], function () use ($router) {
        $router->group(['prefix' => 'user'], function () use ($router) {
            $router->post('register', ['as' => 'admin', 'uses' => 'Auth\RegisterController@register']);
            $router->post('list-user', ['as' => 'user-list', 'uses' => 'User\UserController@listUsers']);
            $router->post('get-user', ['as' => 'user-edit', 'uses' => 'User\UserController@getUser']);
            $router->post('update-user', ['as' => 'admin', 'uses' => 'User\UserController@updateUser']);
            $router->post('assigned-modules', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@getModulePermission']);
            $router->post('assign-modules', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@updateModulePermission']);
            $router->post('role-modules', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@getRoleModules']);
            $router->post('update-role-modules', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@updateRoleModules']);
            $router->post('get-module-items', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@getModuleItem']);
            $router->post('update-module-items', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@updateModuleItem']);
        });
        $router->group(['prefix' => 'bussiness-banking'], function () use ($router) {
            $router->post('bank-list', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@bankList']);
            $router->post('get-bussiness-bank', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@viewBank']);
            $router->post('add-bussiness-bank', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@addBank']);
            $router->post('update-bussiness-bank', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@update']);
            $router->post('add-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@add']);
            $router->post('update-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@updatebankform']);
            $router->post('list-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@listBankForm']);
            $router->post('get-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@showBankForm']);
            $router->post('partner-reports', ['as' => 'business-banking', 'uses' => 'BussinessBanking\PartnerController@partnerList']);
            $router->post('update-partner', ['as' => 'partner-reports', 'uses' => 'BussinessBanking\PartnerController@updatePartner']);
            $router->post('list-cib', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@cibRegistrations']);
            $router->post('get-cib', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@getCib']);
            $router->post('update-cib', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@updateCib']);
            $router->post('update-particular-document', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@updateDocument']);
            $router->post('get-cib-status', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@getSingleCibStatus']);
            $router->post('qr-hit', ['as' => 'partner-requests', 'uses' => 'ExampleController@QrHitOLD']);
            $router->post('modify-cib', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@modifyCib']);
        });
        $router->group(['prefix' => 'charges'], function () use ($router) {
            $router->post('getcharges', ['as' => 'get-charges', 'uses' => 'ChargesController@getCharges']);
            $router->post('update-charges', ['as' => 'save-charges', 'uses' => 'ChargesController@updateCharges']);
            $router->post('default-charges',['as' => 'payments', 'uses' => 'ChargesController@defaultCharges']);
            $router->post('update-default-charges',['as' => 'payments', 'uses' => 'ChargesController@updateDefaultCharges']);
            $router->post('search-users',['as' => 'user-credentials', 'uses' => 'ChargesController@searchUser']);
            $router->post('active-banks',['as' => 'user-credentials', 'uses' => 'ChargesController@activeBanks']);
        });
        // Created by @vinay on 10-10-2024
        $router->group(['prefix' => 'funds'], function () use ($router) {
            $router->post('manual-funding-initial',['as' => 'manual-funding', 'uses' => 'Master\FundsController@manualFundingInitial']);
            $router->post('manual-funding-final',['as' => 'manual-funding', 'uses' => 'Master\FundsController@manualFundingFinal']);
            $router->post('manual-funding-history',['as' => 'manual-funding', 'uses' => 'Master\FundsController@manualFundingHistory']);
        });
        // Created by @vinay on 11-10-2024
        $router->group(['prefix' => 'account'], function () use ($router) {
            $router->post('user-accounts', ['as' => 'user-accounts', 'uses' => 'Master\UserBankController@userAccounts']);
            $router->post('change-status', ['as' => 'change-status', 'uses' => 'Master\UserBankController@changeStatus']);
        });

        // Start - Created by @Vinay
        $router->group(['prefix' => 'new'], function () use ($router) {
            $router->post('add-parent', ['as' => 'parent-add', 'uses' => 'Master\NewPermissionController@addParent']);
            $router->post('add-module', ['as' => 'module-add', 'uses' => 'Master\NewPermissionController@addModule']);
            $router->post('add-permission', ['as' => 'permission-add', 'uses' => 'Master\NewPermissionController@addPermission']);
            $router->post('get-menu', ['as' => 'menu-get', 'uses' => 'Master\NewPermissionController@getSideMenu']);
            $router->post('get-parents', ['as' => 'parents-get', 'uses' => 'Master\NewPermissionController@getParents']);
            $router->post('get-role-permissions', ['as' => 'role-permissions-get', 'uses' => 'Master\NewPermissionController@getRolePermissions']);
            $router->post('get-modules', ['as' => 'modules-get', 'uses' => 'Master\NewPermissionController@getModules']);
            $router->post('get-all-modules', ['as' => 'allmodules-get', 'uses' => 'Master\NewPermissionController@getAllModules']);


            $router->post('add-service', ['as' => 'service-add', 'uses' => 'Master\NewChargesController@addService']);
            $router->post('get-services', ['as' => 'services-get', 'uses' => 'Master\NewChargesController@getAllServices']);
            $router->post('set-default-charges', ['as' => 'defaultcharges-set', 'uses' => 'Master\NewChargesController@setDefaultCharges']);
            $router->post('get-default-charges', ['as' => 'defaultcharges-get', 'uses' => 'Master\NewChargesController@getDefaultCharges']);

            $router->post('get-all-transactions', ['as' => 'alltransactions-get', 'uses' => 'Master\NewTransactionController@allTransactions']);
            $router->post('export-all-transactions', ['as' => 'alltransactions-export', 'uses' => 'Master\NewTransactionController@exportAllTransactions']);
            $router->post('update-payout-status', ['as' => 'payoutstatus-update', 'uses' => 'Master\NewTransactionController@updatePayoutStatus']);
        });
        // End - Created by @Vinay

        $router->group(['prefix' => 'notification'], function () use ($router) {
            $router->post('add-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@addNotification']);
            $router->post('update-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@notificationUpdate']);
            $router->post('list-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@notificationList']);
            $router->post('delete-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@notificationDelete']);
            $router->post('send-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@sendnotification']);
        });
        $router->group(['prefix' => 'menu'], function () use ($router) {
            $router->post('account-types', ['as' => 'account-types', 'uses' => 'Master\FrontSidebarController@accountTypes']);
            $router->post('add-item', ['as' => 'add-item', 'uses' => 'Master\FrontSidebarController@addMenuItem']);
            $router->post('list-item', ['as' => 'list-item', 'uses' => 'Master\FrontSidebarController@listMenuItem']);
            $router->post('get-item', ['as' => 'get-item', 'uses' => 'Master\FrontSidebarController@getMenuItem']);
            $router->post('update-item', ['as' => 'update-item', 'uses' => 'Master\FrontSidebarController@updateMenuItem']);
            $router->post('delete-frontmenu', ['as' => 'delete-frontmenu', 'uses' => 'Master\FrontSidebarController@deletemenu']);
            $router->post('add-role', ['as' => 'admin', 'uses' => 'Master\FrontSidebarController@addRole']);
            $router->post('update-role', ['as' => 'admin', 'uses' => 'Master\FrontSidebarController@updateRole']);
            $router->post('list-role', ['as' => 'admin', 'uses' => 'Master\FrontSidebarController@listRole']);
            $router->post('get-role', ['as' => 'admin', 'uses' => 'Master\FrontSidebarController@updateRole']);
            $router->post('delete-role', ['as' => 'admin', 'uses' => 'Master\FrontSidebarController@getRole']);
            $router->post('get-role-menu', ['as' => 'update-item', 'uses' => 'Master\FrontSidebarController@getRoleItems']);
            $router->post('update-role-menu', ['as' => 'update-item', 'uses' => 'Master\FrontSidebarController@updateRoleItem']);
        });

        $router->group(['prefix' => 'account'], function () use ($router) {
            $router->post('add-account', ['as' => 'add-account', 'uses' => 'Master\AccountController@accounttype']);
            $router->post('get-account', ['as' => 'get-account', 'uses' => 'Master\AccountController@showaccount']);
            $router->post('update-account', ['as' => 'update-account', 'uses' => 'Master\AccountController@updateaccount']);
            $router->post('list-account', ['as' => 'business-banking', 'uses' => 'Master\AccountController@list']);
        });

        $router->group(['prefix' => 'module'], function () use ($router) {
            $router->post('add-module', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@addModule']);
            $router->post('update-module', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@updateModule']);
            $router->post('list-module', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@listModule']);
            $router->post('get-module', ['as' => 'get-module', 'uses' => 'Master\AdminConfigController@getModule']);
            $router->post('add-menu-item', ['as' => 'add-menu-item', 'uses' => 'Master\AdminConfigController@addMenuItem']);
            $router->post('get-menu-item', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@getMenuItem']);
            $router->post('list-menu-item', ['as' => 'list-menu-item', 'uses' => 'Master\AdminConfigController@listMenuItem']);
            $router->post('update-menu-item', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@updateMenuItem']);
        });

        $router->group(['prefix' => 'role'], function () use ($router) {
            $router->post('add-role', ['as' => 'add-role', 'uses' => 'Master\AdminConfigController@addRole']);
            $router->post('update-role', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@updateRole']);
            $router->post('list-role', ['as' => 'admin', 'uses' => 'Master\AdminConfigController@listRole']);
            $router->post('get-role', ['as' => 'get-role', 'uses' => 'Master\AdminConfigController@getRole']);
        });

        $router->group(['prefix' => 'reports'], function () use ($router) {
            $router->post('upi', ['as' => 'collection', 'uses' => 'Reports\UpiController@report']);
            $router->post('exportUpiTransactionList', ['as' => 'collection', 'uses' => 'Reports\UpiController@exportUpiTransactionList']);
            $router->post('exportUpiTransactionListTest', ['as' => 'partner-reports', 'uses' => 'Reports\UpiController@exportUpiTransactionListTest']);
            $router->post('allReport', ['as' => 'partner-reports', 'uses' => 'Reports\UpiController@AllReport']);
            $router->post('vpa', ['as' => 'collection', 'uses' => 'Reports\VpaController@vpa']);
            $router->post('exportVpaList', ['as' => 'collection', 'uses' => 'Reports\VpaController@exportVpaList']);
            $router->post('single-vpa', ['as' => 'partner-reports', 'uses' => 'Reports\VpaController@singleVpa']);
            $router->post('va', ['as' => 'collection', 'uses' => 'Reports\VaController@list']);
            $router->post('exportVaList', ['as' => 'collection', 'uses' => 'Reports\VaController@exportVaList']);
            $router->post('single-va', ['as' => 'partner-reports', 'uses' => 'Reports\VaController@statement']);
            $router->post('va-transaction', ['as' => 'collection', 'uses' => 'Reports\VaController@transactions']);
            $router->post('exportVaTransactionList', ['as' => 'collection', 'uses' => 'Reports\VaController@exportVaTransactionList']);
            $router->post('bene-list', ['as' => 'payments', 'uses' => 'Reports\PayoutController@list']);
            $router->post('exportBeneList', ['as' => 'payments', 'uses' => 'Reports\PayoutController@exportBeneList']);
            $router->post('payout-transactions', ['as' => 'payments', 'uses' => 'Reports\PayoutController@statement']);
            $router->post('exportBeneTransactionList', ['as' => 'payments', 'uses' => 'Reports\PayoutController@exportBeneTransactionList']);
            $router->post('update-vpa', ['as' => 'partner-reports', 'uses' => 'Reports\VpaController@updateVpa']);
            $router->post('update-va', ['as' => 'partner-reports', 'uses' => 'Reports\VaController@updateVa']);
            $router->post('update-payout', ['as' => 'reports-payout', 'uses' => 'Reports\PayoutController@updatePayout']);
            $router->post('get-payout-status', ['as' => 'reports-payout', 'uses' => 'Reports\PayoutController@getPayoutStatus']);
            $router->post('exportcibRegistrations', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@exportcibRegistrations']);
            $router->post('exportpartnerList', ['as' => 'partner-reports', 'uses' => 'BussinessBanking\PartnerController@exportpartnerList']);

            // $router->post('business-trend', ['as' => 'dashboard', 'uses' => 'BusinessController@BusinessTrend']);
            $router->post('user-credentials', ['as' => 'user-credentials', 'uses' => 'User\UserController@userCredentials']);
            $router->post('user-credentials-update', ['as' => 'user-credentials-update', 'uses' => 'User\UserController@userCredentialsUpdate']);
            $router->post('dynamic-form', ['as' => 'business-banking', 'uses' => 'User\UserController@DynamicForm']);

            $router->post('qr-graph-data', ['as' => 'partner-reports', 'uses' => 'Reports\QrController@QrGraphData']);
            $router->get('download-report', ['as' => 'reports-payout', 'uses' => 'Reports\DownloadReportController@DownloadReport']);

            $router->post('get-va-transactions', ['as' => 'dashboard', 'uses' => 'DashboardController@getVaTransactions']);
            $router->post('get-qr-transactions', ['as' => 'dashboard', 'uses' => 'DashboardController@getQrTransactions']);
            $router->post('get-payout-transactions', ['as' => 'dashboard', 'uses' => 'DashboardController@getPayoutTransactions']);

        });

        $router->group(['prefix' => 'payout'], function () use ($router) {
            $router->post('bank-details-store', ['as' => 'user-credentials', 'uses' => 'Reports\PayoutController@BankDetailsStore']);
            $router->post('bank-details-update', ['as' => 'user-credentials', 'uses' => 'Reports\PayoutController@BankDetailsUpdate']);
            $router->post('get-bank-details', ['as' => 'user-credentials', 'uses' => 'Reports\PayoutController@GetBankDetails']);
        });

        $router->group(['prefix' => 'configuration'], function () use ($router) {
            $router->post('admin', ['as' => 'admin-configuration', 'uses' => 'ConfigurationController@admin']);
            $router->post('front', ['as' => 'front-configuration', 'uses' => 'ConfigurationController@front']);
        });
    });
});
$router->post('request-report-download', ['as' => 'request-report-download', 'uses' => 'Master\ReportDownloadController@reportDownload']);
$router->group(['prefix' => 'api', 'middleware' => ['auth:api', 'crypt', 'logs']], function () use ($router) {

    /*..................... User route........................... */
    $router->post('user/list', ['as' => 'user-list', 'uses' => 'User\UserController@index']);
    $router->post('user/update', ['as' => 'user-update', 'uses' => 'User\UserController@update']);

    /*.....................@module............................ */
    $router->post('module/add', ['as' => 'module-add', 'uses' => 'Auth\PermissionController@addModule']);
    $router->post('module/delete', ['as' => 'module-delete', 'uses' => 'Auth\PermissionController@deleteModule']);
    $router->post('module/list', ['as' => 'module-list', 'uses' => 'Auth\PermissionController@moduleList']);

    /*.....................@permission............................ */
    $router->post('permission/add', ['as' => 'permission-add', 'uses' => 'Auth\PermissionController@addCustomPermission']);
    $router->post('permission/delete', ['as' => 'permission-delete', 'uses' => 'Auth\PermissionController@deletePermission']);
    $router->post('permission/list', ['as' => 'permission-list', 'uses' => 'Auth\PermissionController@PermissionList']);
    $router->post('permission/update', ['as' => 'permission-delete', 'uses' => 'Auth\PermissionController@updatePermission']);

    /*.....................@role............................ */
    $router->post('role/add', ['as' => 'role-add', 'uses' => 'Auth\PermissionController@addRole']);
    $router->post('role/edit', ['as' => 'role-update', 'uses' => 'Auth\PermissionController@updateRole']);
    $router->post('role/delete', ['as' => 'role-delete', 'uses' => 'Auth\PermissionController@deleteRole']);
    $router->post('role/list', ['as' => 'role-list', 'uses' => 'Auth\PermissionController@RoleList']);
    $router->post('role/view', ['as' => 'role-view', 'uses' => 'Auth\PermissionController@getRoleById']);
});