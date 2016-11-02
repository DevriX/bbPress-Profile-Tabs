# bbPress Profile Tabs
WordPress bbPress Profile Tabs class allows you to easily create tabs with custom content and add tab items to the nav menu, besides accessibility controle made simple

# Example Use

1. Load the class:

<pre><code>if ( !class_exists('bbPressProfileTabs') ) {
    require ABSPATH . '/wp-content/plugins/bbpress-profile-tabs/bbpress-profile-tabs.php';
}</code></pre>

2. Using `create` method to register the tab:

<pre><code>bbPressProfileTabs::create(
    [
        'slug' => 'my-custom-tab',
        'menu-item-text' => 'My Custom Tab',
        'menu-item-position' => 1,
        'visibility' => 'logged-in'
    ]
);</code></pre>

3. Now we embed the tab content:

Hook into `BPT_content-{my_tab_slug}` replacing `{my_tab_slug}` with the slug you specify for your tab in the previous step. Here's an example:

<pre><code>add_action( "BPT_content-my-custom-tab", function() {
    // get displayed user data
    $displayedUser = get_userdata( bbp_get_displayed_user_id() );
    ?>

    <h2 class="entry-title">My Custom Tab</h2>
    
    <p>Hello Folks! I created <?php echo $displayedUser->display_name; ?>'s custom tab with bbPress Profile Tabs from @Samuel_Elh!</p>

    <?php
});</code></pre>

# Quick Docs

Coming soon..
