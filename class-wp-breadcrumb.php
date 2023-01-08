<?php

/**
 * Wp_Breadcrumb
 * 
 * @author Giulio Delmastro <https://comshake.com>
 * 
 */

class Wp_Breadcrumb
{

    protected $crumbs;

    protected $separetor;

    protected $breadcrumb_class;

    protected $breadcrumb_item_class;

    /**
     * Constructor.
     * @param string $separetor separetor for breadcrumb item.
     * @param string $breadcrumb_class css class for breadcrumb.
     * @param string $breadcrumb_item_class css class for breadcrumb item.
     */
    public function __construct($separetor = '>', $breadcrumb_class = 'breadcrumb', $breadcrumb_item_class = 'breadcrumb__item')
    {
        $this->separetor = $separetor;
        $this->breadcrumb_class = $breadcrumb_class;
        $this->breadcrumb_item_class = $breadcrumb_item_class;
    }

    /**
     * 
     * Display HTML breadcrumb
     * 
     */
    public function display()
    {

        $this->generate();
        $this->search_trail();
        $this->paged_trail();

        $html = '<nav aria-label="Breadcrumb" class="' . $this->breadcrumb_class . '"><ol>';

        foreach ($this->crumbs as $index => $crumb) {
            $html .= '<li class="' . $this->breadcrumb_item_class . '"> <a href="' . $crumb['link'] . '">' . $crumb['title'] . ' </a></li>';

            if ((array_key_last($this->crumbs) != $index)) {
                $html .= '<span>' . $this->separetor . '</span>';
            }
        }

        $html .= '</ol></nav>';

        echo $html;
    }

    /**
     * Generate trails
     */
    protected function generate()
    {
        $conditionals = array(
            'is_front_page',
            'is_home',
            'is_404',
            'is_single',
            'is_page',
            'is_post_type_archive',
            'is_category',
            'is_tag',
            'is_author',
            'is_date',
            'is_tax',
        );


        foreach ($conditionals as $conditional) {
            if (call_user_func($conditional)) {
                call_user_func(array($this, 'add_crumbs_' . substr($conditional, 3)));
                break;
            }
        }
    }

    /**
     * Add trail
     *
     * @param int    $post_id   Post ID.
     * @param string $permalink Post permalink.
     */
    protected function add_crumb($crumb_title, $crumb_link = '#')
    {

        $this->crumbs[] = array(
            'title' => wp_strip_all_tags($crumb_title),
            'link' => $crumb_link,
        );
    }

    /**
     * Add trail for homepage
     */
    protected function add_crumbs_front_page()
    {
        $this->add_crumb('Home', get_home_url());
    }

    /**
     * Add trail for blog page
     */
    protected function add_crumbs_home()
    {
        $post = get_queried_object();
        $this->add_crumbs_front_page();
        $this->add_crumb(get_the_title($post->ID), '');
    }

    /**
     * Add trail for page
     */
    protected function add_crumbs_page()
    {
        global $post;

        $this->add_crumbs_front_page();

        if ($post->post_parent) {
            $parent_crumbs = [];
            $parent_id     = $post->post_parent;

            while ($parent_id) {
                $page            = get_post($parent_id);
                $parent_id       = $page->post_parent;
                $parent_crumbs[] = array(get_the_title($page->ID), get_permalink($page->ID));
            }

            $parent_crumbs = array_reverse($parent_crumbs);

            foreach ($parent_crumbs as $crumb) {
                $this->add_crumb($crumb[0], $crumb[1]);
            }
        }

        $this->add_crumb(get_the_title(), get_permalink());
    }

    /**
     * Add trail for Single post.
     *
     * @param int    $post_id   Post ID.
     * @param string $permalink Post permalink.
     */
    protected function add_crumbs_single($post_id = 0, $permalink = '')
    {
        if (!$post_id) {
            global $post;
        } else {
            $post = get_post($post_id); // WPCS: override ok.
        }

        if (!$permalink) {
            $permalink = get_permalink($post);
        }

        $this->add_crumbs_front_page();

        if ('post' !== get_post_type($post)) {
            $post_type = get_post_type_object(get_post_type($post));

            if (!empty($post_type->has_archive)) {
                $this->add_crumb($post_type->labels->name, get_post_type_archive_link(get_post_type($post)));
            }
        } else {
            $post_type = get_post_type_object(get_post_type($post));
            $this->add_crumb($post_type->labels->name, get_post_type_archive_link(get_post_type($post)));
            $cat = current(get_the_category($post));
            if ($cat) {
                $this->term_ancestors($cat->term_id, 'category');
                $this->add_crumb($cat->name, get_term_link($cat));
            }
        }

        $this->add_crumb(get_the_title($post), $permalink);
    }

    /**
     * Add trail for 404.
     */
    protected function add_crumbs_404()
    {
        $this->add_crumbs_front_page();
        $this->add_crumb(__('Error 404', 'wp'));
    }

    /**
     * Add trail for single post.
     *
     * @param int    $post_id   Post ID.
     * @param string $permalink Post permalink.
     */
    protected function add_crumbs_post_type_archive()
    {

        $this->add_crumbs_front_page();

        $post_type = get_queried_object();

        if ($post_type) {
            $this->add_crumb($post_type->labels->name, get_post_type_archive_link(get_post_type()));
        }
    }

    /**
     * Add trail for category term archive.
     */
    protected function add_crumbs_category()
    {
        $this_category = get_category($GLOBALS['wp_query']->get_queried_object());

        if (0 !== intval($this_category->parent)) {
            $this->term_ancestors($this_category->term_id, 'category');
        }

        $this->add_crumb(single_cat_title('', false), get_category_link($this_category->term_id));
    }


    /**
     * Add trail for tag term archive.
     */
    protected function add_crumbs_tag()
    {
        $queried_object = $GLOBALS['wp_query']->get_queried_object();

        $this->add_crumb(sprintf('Posts tagged &ldquo;%s&rdquo;', single_tag_title('', false)), get_tag_link($queried_object->term_id));
    }

    /**
     * Add trail for custom post type archive.
     */
    protected function add_crumbs_tax()
    {
        $this_term = $GLOBALS['wp_query']->get_queried_object();
        $taxonomy  = get_taxonomy($this_term->taxonomy);

        $this->add_crumbs_front_page();
        $this->add_crumb($taxonomy->labels->name, '');

        if (0 !== intval($this_term->parent)) {
            $this->term_ancestors($this_term->term_id, $this_term->taxonomy);
        }

        $this->add_crumb(single_term_title('', false), get_term_link($this_term->term_id, $this_term->taxonomy));
    }

    /**
     * Add trail for author archive.
     */
    protected function add_crumbs_author()
    {
        global $author;

        $userdata = get_userdata($author);

        $this->add_crumb($userdata->display_name, get_author_posts_url($userdata));
    }


    /**
     * Add trail for date archive.
     */
    protected function add_crumbs_date()
    {
        if (is_year() || is_month() || is_day()) {
            $this->add_crumb(get_the_time('Y'), get_year_link(get_the_time('Y')));
        }
        if (is_month() || is_day()) {
            $this->add_crumb(get_the_time('F'), get_month_link(get_the_time('Y'), get_the_time('m')));
        }
        if (is_day()) {
            $this->add_crumb(get_the_time('d'));
        }
    }

    /**
     * Add trail for a term.
     *
     * @param int    $term_id  Term ID.
     * @param string $taxonomy Taxonomy.
     */

    protected function term_ancestors($term_id, $taxonomy)
    {
        $ancestors = get_ancestors($term_id, $taxonomy);
        $ancestors = array_reverse($ancestors);

        foreach ($ancestors as $ancestor) {
            $ancestor = get_term($ancestor, $taxonomy);

            if (!is_wp_error($ancestor) && $ancestor) {
                $this->add_crumb($ancestor->name, get_term_link($ancestor));
            }
        }
    }


    /**
     * Add trail for search results page.
     */
    protected function search_trail()
    {
        if (is_search()) {
            $this->add_crumbs_front_page();
            $this->add_crumb(sprintf('Search results for %s', get_search_query()), remove_query_arg('paged'));
        }
    }

    /**
     * Add a breadcrumb for pagination.
     */
    protected function paged_trail()
    {
        if (get_query_var('paged')) {
            $this->add_crumb(sprintf('Page %d', get_query_var('paged')));
        }
    }
}
