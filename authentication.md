Authentication
======================

##Configuration

The security configuration is set up in app/config/security.yaml


Concretely, here we can see that the users are stored in the database.
Users passwords are hashed using the bcrypt hash function.
all URLs starting with "/" (basically the entire site) require authentication as a user, with the exception of the url /login which requires no authentication.
Urls starting with / users require to be authenticated as admin

You can check the [Symfony documentation](https://symfony.com/doc/current/reference/configuration/security.html) for more details about this file

```yaml
# app/config/security.yaml

security:
    encoders:
        App\Entity\User: bcrypt

    providers:
        doctrine:
            entity:
                class: App\Entity\User
                property: username

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            pattern: ^/
            anonymous: true
            guard:
                authenticators: [App\Security\LoginFormAuthenticator]
            logout:
                path: app_logout


    access_control:
        - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/users, roles: ROLE_ADMIN }
        - { path: ^/, roles: ROLE_USER }
```

##Implementation of authentication
To implement authentication I simply followed the [Symfony guide](https://symfony.com/doc/current/security/form_login_setup.html).

Firstly thanks to the Symfony MakerBundle I generated the useful files for authentication via the command
```
   php bin/console make:auth
```

this command generates :
- the SecurityController if it does not already exist
- the Guard authenticator class that I named LoginFormAuthenticator (src/Controller/Security/LoginFormAuthenticator.php)
- A login form (templates/security/login.html.twig)


In the SecurityController there are two methods that have been generated: <b>login</b> and <b>logout</b>.<br/>
The logout method is empty because it will be intercepted by the logout key on your firewall.<br/>
```php
        /**
         * @Route("/logout", name="app_logout")
         */
        public function logout()
        {
            throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
        }
```
The login method recovers any connection errors and the last username entered by the user before communicating them to the twig template responsible for displaying the login form, this template simply contains an html form that submit to /login

```php
     /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
```


The Guard authenticator class processes the login submit. 
I will not describe the entire methods that make up this class, you can find more information in the [Symfony documentation](https://symfony.com/doc/current/security/form_login_setup.html)
The only thing I had to do is indicate where I wanted the user to be redirected after success:
```php
public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // ...

        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }
```

##How authentication works

To connect, the user will access the page where the connection form is located (/login).<br>
From this form, the user will enter a username and password and submit the form, once the form is submitted, it is the class LoginFormAuthenticator (src/Controller/Security/LoginFormAuthenticator.php)
which will search in the database (we have defined that the users are stored in the database in the security.yaml file) if the user exists and if the password actually corresponds to the user.<br>
If this is not the case an error message will be displayed otherwise the user will be logged in and redirected to the home page as we defined in the onAuthenticationSuccess method.