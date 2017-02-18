# bbPress Profile Tabs
WordPress bbPress Profile Tabs class allows you to easily create tabs with custom content and add tab items to the nav menu, besides accessibility controle made simple

# Example Use

1. Load the class:

```php
if ( !class_exists('bbPressProfileTabs') ) {
    require('bbPressProfileTabs.php');
}
```

2. Using <code>create</code> method to register the tab:

```php
add_action('plugins_loaded', 'register_my_custom_profile_tab');

function register_my_custom_profile_tab() {
    return \bbPressProfileTabs::create(
        [
            'slug' => 'my-custom-tab',
            'menu-item-text' => 'My Custom Tab',
            'menu-item-position' => 1,
            'visibility' => 'logged-in'
        ]
    )
    /* You must call the init method */
    ->init();
}
```

3. Now we embed the tab content:

Hook into `BPT_content-{my_tab_slug}` replacing `{my_tab_slug}` with the slug you specify for your tab in the previous step. Here's an example:

```php
add_action( "BPT_content-my-custom-tab", function() {
    // get displayed user data
    $displayedUser = get_userdata( bbp_get_displayed_user_id() );
    ?>

    <h2 class="entry-title">My Custom Tab</h2>
    
    <p>Hello Folks! I created <?php echo $displayedUser->display_name; ?>'s custom tab with bbPress Profile Tabs from @Samuel_Elh!</p>

    <?php
});
```

**Important**: The rewrite rules will have to be flushed so the registered tabs will function so if you are using this on a custom plugin then flush the rewrite rules using [`register_activation_hook`](https://developer.wordpress.org/reference/functions/register_activation_hook/). `bbPressProfileTabs::flushRewriteRules()` can be helpful.

# Quick Docs

Coming soon..
