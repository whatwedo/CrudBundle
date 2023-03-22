# Login

Follow Symfony's [documentation](https://symfony.com/doc/current/security.html) to configure your security.

## Form

If you are using a standard Symfony Form Login you can use our template for that as following:

LoginController.php
```php
#[Route('/login', name: 'app_login')]
public function login(AuthenticationUtils $authenticationUtils): Response
{
    return $this->render('@whatwedoCrud/Login/login.html.twig', [
        'error' => $authenticationUtils->getLastAuthenticationError(),
        'last_username' => $authenticationUtils->getLastUsername(),
    ]);
}
```

## Template
You can customize the `login.html.twig` template. The template extends from the `base.html.twig` and provides additionally the following blocks and message keys:

### Blocks
`login_path` - The path to the login route

### Message keys
`login.title` - The title of the login page
`login.username` - The label of the username field
`login.password` - The label of the password field
`login.submit` - The label of the submit button
