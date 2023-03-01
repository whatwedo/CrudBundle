# How to apply custom styles to your CRUD

In case you want to change some styling specific to your project, you can do so. We don't enforce special rules for developers to adjust stylings. But we help you with certain things to keep the code clean and readable.

## Style Hooks

On most significant HTML elements we apply custom classes. Their purpose is to allow developers working with the CRUD bundle, to adjust the style accordingly.
It's very easy to hook into them and you shouldn't run into specifity issues.

## Script Hooks

We don't mix styling with functionality. We never use a Style Hook to add a piece of JavaScript magic to the element. Since we're using Stimulus, functionality is isolated and initiated within a controller and it's `data-controller` attribute.

## What needs to be done?

The process is very simple. Since you already created a main stylesheet to apply the build-in stylings of the bundle, you're ready to go.
Below the imports you can target our style hooks and adjust stylings accordingly.

Here's an example:

```scss
@import "~@whatwedo/core-bundle/styles/_tailwind.scss";
@import "~@whatwedo/table-bundle/styles/_tailwind.scss";

// CUSTOM STYLINGS
.whatwedo_table-header {
    background-color: red; // example styling
}
```

## What can be used within the main style

Since your main style is probably a SCSS file you can use SCSS functionalities and syntax as usual. In addition we're importing Tailwind, which mean you also have access to all variables and classes of Tailwind. Check out their documentation. We internally use Tailwind for everything.
It's very easy to apply a Tailwind class to your target by using the `@apply` keyword.
All the options set inside the `tailwind.config.js` config file you can use as in every Tailwind project.

```scss
.whatwedo_core-navigation {
    @apply flex flex-col justify-center;
    @apply text-error-500
}
```

## Naming conventions

Our style hooks are following a naming convention which is similar to the BEM methodology.
We append the name of the bundle to the element at the beginning, so we can easly know where the element is coming from. Afterwards the elements follow.

Have a look in the examples below:

**Header** (`whatwedo_table-header`) → Block

**Toolbar within the  Header** (`whatwedo_table-header__toolbar`) → Element

**Special Header** (`whatwedo_table-header--special`) → Modifier

**Special Toolbar within the header** (`whatwedo_table-header__toolbar--special`) → Modifier

## There's no hook for what I want to style

There are for sure cases, where you don't find a style hook at the place you need it.
You can always just target a HTML element, but I highly suggest you to include a style hook as a parent to isolate the styling and give context.

For example you want to adjust something within a navigation item:

```scss
.whatwedo_core-navigation__item strong {
    font-weight: 900;
} 
```

Now you only target elements within the navigation item and it's very readable.
In the end you can do whatever, but we tried to give you those helpful options to keep also your custom code clean and the specifity low (we don't like to leave you with using `!important`).

If you want to adjust more then just a simple styling or even add something custom, then you need to find the correct template to override and change your markup and classes within a TWIG file.
