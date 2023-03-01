# Dashboard

In the CRUD bundle there is a dashboard page which is also displayed by default as a navigation point. This section explains how to edit the dashboard page.

## Custom HTML
If you want to use your own HTML on the dashboard page, you can do it like this:
1. Create a file in `templates/whatwedoCrudBundle/dashboard.html.twig` or you can copy it from the CRUD bundle.
   The file should look like this:

```twig
{% extends 'base.html.twig' %}

{% block main %}
    Your html content
{% endblock %}
```

In the main block you can now insert your own HTML.

## Custom logic

You may also want to use your own logic and display, for example, the number of customers on the dashboard. You can do that as follows:
1. Create a new Controller `DashboardController.php` and create a function for the `dashboard` route. The file should look like this:

```php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dashboard.html.twig', [
            'customerCounter' => 100,
        ]);
    }
}
```

Create a new file in `templates/dashboard.html.twig`. The file should no longer be in the `whatwedoCrudBundle` folder.

## Keep the /dashboard route
If you want to use your own dashboard logic and it should be callable with /dashboard, you can do it like this:
1. Adjust the `config/routes/whatwedo_crud.yaml` like this:

```yaml
whatwedo_crud:
    resource: .
    type: whatwedo_crud

whatwedo_crud_crud_select_ajax:
    path: /whatwedo/crud/select/ajax
    defaults: { _controller: whatwedo\CrudBundle\Controller\RelationController::ajaxAction }
```

The entry with the key `whatwedo_crud_dashboard` should no longer exist in the file.

2. Create the file `templates/base.html.twig`, if it does not exist yet. Overwrite the `logo` block with the `dashboard` route.
   The file should look like this:

```twig
{% extends '@whatwedoCrud/base.html.twig' %}

{% block logo %}
    <a href="{{ path('dashboard') }}">
        <img class="h-6 w-auto" src="https://static.whatwedo.io/whatwedo-logo.svg" alt="whatwedo">
    </a>
{% endblock %}
```

That's all, when you call `/dashboard` your controller should now be used.
