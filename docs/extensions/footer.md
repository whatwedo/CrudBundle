# Footer

In CRUD Bundle we have a footer in the side navigation, which can be customized as follows:
1. Create a file in `templates/whatwedoCrudBundle/includes/sidebar/_footer.html.twig` or you can copy it from the CRUD bundle.
   The file should look like this:

```twig
{% block sidebar_profile %}
    <a href="{% block logout_link %}{% endblock %}" class="whatwedo_crud-profile lex-shrink-0 flex border-t border-neutral-200 p-4">
        <div class="flex items-center">
            <div>
                <img class="inline-block h-10 w-10 rounded-full"
                     src="{% block profile_picture %}{% endblock %}" alt=""
                     crossorigin="anonymous" referrerpolicy="no-referrer">
            </div>
            <div class="ml-3">
                <p class="text-base font-medium text-neutral-700 group-hover:text-neutral-900">
                    {% block profile_name %}{% endblock %}
                </p>
                <p class="text-sm font-medium text-neutral-500 group-hover:text-neutral-700">
                    {% block logout_text %}Logout{% endblock %}
                </p>
            </div>
        </div>
    </a>
{% endblock %}
```

You can now delete all the content and insert your own HTML.

If you only want to customize the profile image and logout link, you can do it as follows:

```twig
{% extends '@!whatwedoCrud/includes/sidebar/_footer.html.twig' %}

{% block profile_picture %}https://example.ch/example.jpg{% endblock %}

{% block logout_link %}{{ path('logout_route') }}{% endblock %}
```
