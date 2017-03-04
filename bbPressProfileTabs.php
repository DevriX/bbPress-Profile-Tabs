<?php

/**
  * WordPress bbPress Profile Tabs class
  * allows you to easily create tabs with custom content
  * and add tab items to the nav menu, besides accessibility
  * controle made simple
  *
  * @author Samuel Elh <samelh.com/contact>
  * @link https://github.com/elhardoum/bbPress-Profile-Tabs
  */

class bbPressProfileTabs
{
    /**
      * Class instance for static calls
      */
    protected static $instance = null;

    /** Get Class instance **/
    public static function instance()
    {
        return null == self::$instance ? new self : self::$instance;
    }

    public function init()
    {
        // initialize the tabs
        $this->initTabs();
        // init tabs rewrite rules
        add_action('init', array($this, 'rewriteRules'));
        // admin check
        if ( is_admin() )
            return $this;
        // add CSS hack to hide bbP profile elements and show our tab instead
        add_action( "wp_head", array( $this, "cssHack" ) );
        // add menu items with JavaScript
        add_action( "wp_footer", array( $this, "jsHack" ) );
        // append query variable for identifying tabs
        add_filter( "query_vars", array( $this, "pushQueryVar" ) );
        // accessibility check
        add_action( "wp", array( $this, "accessibilityCheck" ), 0 );
        // parse the custom tab content
        add_action( "bbp_template_before_user_profile", array( $this, "parseTabContent" ) );
        // return an instance
        return $this;
    }

    public static function create( Array $args )
    {
        global $BPT_tabs;
        // setup the global var
        if ( !isset( $BPT_tabs ) || !is_array($BPT_tabs) ) {
            $BPT_tabs = array();
        }
        // check for slug
        if ( !isset($args['slug']) ) {
            throw new \Exception("Error registering bbPress Profile Tab: A custom slug must be set within the argument passed to bbPressProfileTabs::create() method");
        }
        // append the tab data
        $BPT_tabs[] = $args;

        return self::instance();
    }

    public function initTabs()
    {
        global $BPT_tabs;
        // if no tabs are registered yet
        if ( empty( $BPT_tabs ) ) return;
        // users rewrite base
        if ( !function_exists('bbp_get_user_slug') ) {
            // bbp not there, why bother continue?
            return;
        } else {
            $user_slug = bbp_get_user_slug();
        }
        // 
        global $BPT_rewrites;
        if ( !isset($BPT_rewrites) || !is_array($BPT_rewrites) ) {
            $BPT_rewrites = array();
        }
        // append the rewrite rules for tabs
        foreach ( $BPT_tabs as $i => $tab ) {
            // slug or regex
            $slug = sprintf(
                '%s/([^/]+)/%s/?$',
                $user_slug,
                $tab['slug']
            );
            // append the query
            $BPT_rewrites[$slug] = sprintf(
                'index.php?bbp_user=$matches[1]&BPT_tab=%s',
                isset($tab['query_var']) ? $tab['query_var'] : $tab['slug']
            );
        }

        return $this;
    }

    public function rewriteRules()
    {
        global $BPT_rewrites;

        if ( empty($BPT_rewrites) || !is_array($BPT_rewrites) ) return;
        foreach ( $BPT_rewrites as $regex => $query ) {
            add_rewrite_rule( $regex, $query, 'top' );
        }

        return $this;
    }

    public function pushQueryVar( $vars )
    {
        $vars[] = "BPT_tab";
        return $vars;
    }

    public function cssHack()
    {
        // not our tab, none of our concern
        if ( !get_query_var( 'BPT_tab' ) ) return;
        // print some styles to hide bbP profile elements, not ours
        // make sure to use !important with your display tweaks, sorry!
        print('<style type="text/css" id="BPT_hack">/* a CSS hack to hide other profile content while viewing our custom tab. bbPress is kinda evil to not provide useful profile hooks for making this process much simple.. T_T */' . PHP_EOL);
        print('.BPT-content,.BPT-content *{display: inherit !important;}#bbp-user-body * {display:none}</style>');
    }

    public function jsHack()
    {
        // get displayed user ID
        $user_id = bbp_get_displayed_user_id();
        // not bbP profile, none of our concern
        if ( !$user_id ) return;
        // get tab id
        $query_var = get_query_var( 'BPT_tab' );
        global $BPT_tabs;
        // current tab for JS menu
        $currentTab = null;
        // setup current tab
        foreach ( $BPT_tabs as $i => $tab ) {
            $id = isset($tab['query_var']) ? $tab['query_var'] : $tab['slug'];
            if ( $id === $query_var ) {
                $currentTab = $i;
                break;
            }
        }
        $items = array();
        // profile URL
        $profileUrl = apply_filters(
            'BPT_user_profile_url',
            home_url( bbp_get_user_slug() . '/' . get_userdata( $user_id )->user_nicename . '/' ),
            $user_id
        );
        foreach ( $BPT_tabs as $i => $tab ) {   
            $item = sprintf(
                '<li class="BPT-tab tab-%s%s">',
                preg_replace('/[^\da-z]/i', '', $tab['slug']),
                $i === $currentTab ? ' current' : ''
            );
            $item .= '<span>';
            $item .= sprintf(
                '<a href="%s">%s</a>',
                "{$profileUrl}{$tab['slug']}/",
                isset($tab['menu-item-text']) ? $tab['menu-item-text'] : $tab['slug']
            );
            $item .= '</span></li>';
            // append item HTML with menu position
            $items[$item] = isset($tab['menu-item-position']) ? (int) $tab['menu-item-position'] : null;
        }
        // filterable
        $items = apply_filters( "BPT_JS-menu_items", $items, $user_id, $profileUrl );
        // check for items
        if ( !$items ) return;
        ?>
        <script type="text/javascript" id="BPT_menu">
            var BPT_init = function() {
                <?php if ( is_numeric($currentTab) ) : ?>
                    var current = document.querySelector("#bbp-user-navigation li.current");
                    null!==current&&current.classList.remove("current");
                <?php endif; ?>
                var items = document.querySelectorAll("#bbp-user-navigation li");
                <?php foreach ( $items as $item => $position ) : ?>
                    <?php if ( is_numeric($position) ) : ?>
                        var target = "undefined" !== typeof items[<?php echo $position; ?>] ? items[<?php echo $position; ?>] : (
                            "undefined" !== typeof items[items.length-1] ? items[items.length-1] : null
                        );
                    <?php else : ?>
                        if ( "undefined" !== typeof items[items.length-1] ) {
                            var target = items[items.length-1];
                        }
                    <?php endif; ?>
                    if ( "undefined" !== typeof target ) {
                        var list = document.querySelector('#bbp-user-navigation ul');
                        var li = document.createElement("LI");
                        li.innerHTML= '<?php echo $item; ?>';
                        var li = li.firstChild;
                        list.insertBefore( li, target );
                    } else {
                        console.error("BPT Error: Could not find an item position for a profile element (%s)",'<?php echo $item; ?>');
                    }
                <?php endforeach; ?>
            }
            BPT_init();
        </script>
        <?php
    }

    public static function parseTabContent()
    {
        // get tab ID from the query
        $query_var = get_query_var( "BPT_tab" );
        // no tab set, bail
        if ( !$query_var ) return;
        print('<div class="BPT-content">');
        global $BPT_tabs;
        if ( $BPT_tabs && is_array($BPT_tabs) ) {
            foreach ( $BPT_tabs as $i => $tab ) {
                $id = isset($tab['query_var']) ? $tab['query_var'] : $tab['slug'];
                if ( $id === $query_var ) {
                    // trigger a hook for parsing tab content
                    do_action( "BPT_content-{$id}" );
                    // there has to be one tab with this id
                    break;
                }
            }
        }
        print('</div>');
    }

    public static function accessibilityCheck()
    {
        // get tab ID from the query
        $query_var = get_query_var( "BPT_tab" );
        // no tab set, bail
        if ( !$query_var ) return;
        global $BPT_tabs;
        // check for registered tabs
        if ( !$BPT_tabs ||!is_array($BPT_tabs) ) return;
        // trigger hook
        do_action( "BPT_tab-redirect" );

        foreach ( $BPT_tabs as $i => $tab ) {
            $id = isset($tab['query_var']) ? $tab['query_var'] : $tab['slug'];
            if ( $id === $query_var ) {
                // trigger hooj
                do_action( "BPT_tab-{$tab['slug']}-redirect" );

                if ( !empty( $tab['visibility'] ) ) {
                    switch ( strtolower($tab['visibility']) ) {

                        case 'logged-in':
                            if ( !is_user_logged_in() ) {
                                wp_redirect( apply_filters('BPT_login_url', wp_login_url( $_SERVER['REQUEST_URI'] )) );
                                exit;
                            }
                            break;

                        case 'profile-owner':
                        case 'profile-owner-and-admin':
                            if ( !is_user_logged_in() ) {
                                wp_redirect( apply_filters('BPT_login_url', home_url('/')) );
                                exit;
                            }
                            $user_id = bbp_get_displayed_user_id();
                            if ( $user_id !== get_current_user_id() ) {
                                // exclude admin
                                if ( "profile-owner-and-admin" === strtolower($tab['visibility']) ) {
                                    if ( current_user_can("manage_options") ) return;
                                }
                                wp_redirect( apply_filters('BPT_login_url', home_url('/')) );
                                exit;
                            }
                            break;

                    }
                }

                break;
            }
        }
    }

    public static function flushRewriteRules()
    {
        /**
          * delete the rules so they can be renewed
          */
        return delete_option( "rewrite_rules" );
    }

    /**
      * Check if current tab is $name
      * You must call this on wp query ready
      * i.e on wp, wp_loaded, not init or plugins_loaded, and early hooks
      */
    public function isTab($name)
    {
        return $name == get_query_var('BPT_tab');
    }
}