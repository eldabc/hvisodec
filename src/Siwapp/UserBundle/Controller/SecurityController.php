<?php

namespace Siwapp\UserBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends \FOS\UserBundle\Controller\SecurityController
{
    /**
     * We override loginAction to redirect the user depending on their role.
     * If they try to go to /login, they will be redirected accordingly based on their role
     *
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request)
    {
        $auth_checker = $this->get('security.authorization_checker');
        $router = $this->get('router');
        
        if ($auth_checker->isGranted('ROLE_GESTOR')) {
            // Everyone else goes to the `home` route
            return new RedirectResponse($router->generate('tax_index'), 307);
        }

        // 307: Internal Redirect
        if ($auth_checker->isGranted(['ROLE_SUPER_ADMIN'])) {
            // SUPER_ADMIN roles go to the `admin_home` route
            return new RedirectResponse($router->generate('dashboard_index'), 307);
        }

        

        // Always call the parent unless you provide the ENTIRE implementation
        return parent::loginAction($request);
    }
}