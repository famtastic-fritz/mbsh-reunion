<?php

declare(strict_types=1);

final class Famtastic_Reunion_Content
{
    public static function register(): void
    {
        add_action('init', [self::class, 'register_types']);
    }

    public static function register_types(): void
    {
        self::post_type('reunion_memory', 'Memories', 'Memory', true, ['title', 'editor', 'author', 'thumbnail']);
        self::post_type('reunion_suggestion', 'Suggestions', 'Suggestion', false, ['title', 'editor', 'author']);
        self::post_type('reunion_ticket', 'Virtual Tickets', 'Virtual Ticket', false, ['title', 'author']);
        self::post_type('reunion_event', 'Events', 'Event', true, ['title', 'editor', 'thumbnail']);
        self::post_type('reunion_component', 'Page Components', 'Page Component', false, ['title', 'editor', 'thumbnail', 'revisions', 'page-attributes']);
        self::post_type('reunion_faq', 'FAQs & Harry Knowledge', 'FAQ', true, ['title', 'editor', 'revisions', 'page-attributes']);
        self::post_type('reunion_announcement', 'Announcements', 'Announcement', true, ['title', 'editor', 'thumbnail', 'revisions']);
        self::post_type('reunion_sponsor', 'Sponsors', 'Sponsor', true, ['title', 'editor', 'thumbnail', 'revisions']);
        self::post_type('reunion_tribute', 'Tributes', 'Tribute', false, ['title', 'editor', 'thumbnail', 'revisions']);
        self::post_type('reunion_trivia', 'Trivia Games', 'Trivia Game', false, ['title', 'editor', 'revisions']);
        self::post_type('reunion_trivia_question', 'Trivia Questions', 'Trivia Question', false, ['title', 'editor', 'revisions', 'page-attributes']);

        register_taxonomy('reunion_memory_topic', ['reunion_memory'], [
            'label' => 'Memory Topics',
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
        ]);
        register_taxonomy('reunion_component_page', ['reunion_component'], ['label'=>'Page placement','public'=>false,'show_ui'=>true,'show_in_rest'=>true,'hierarchical'=>true]);
        register_taxonomy('reunion_audience', ['reunion_component','reunion_announcement','reunion_faq'], ['label'=>'Audience','public'=>false,'show_ui'=>true,'show_in_rest'=>true,'hierarchical'=>true]);
        foreach(['_famtastic_component_type','_famtastic_visibility','_famtastic_starts_at','_famtastic_ends_at','_famtastic_cta_label','_famtastic_cta_url','_famtastic_owner'] as $key){
            register_post_meta('reunion_component',$key,['type'=>'string','single'=>true,'show_in_rest'=>true,'auth_callback'=>fn()=>current_user_can('famtastic_manage_reunion'),'sanitize_callback'=>'sanitize_text_field']);
        }
    }

    private static function post_type(string $type, string $plural, string $singular, bool $public, array $supports): void
    {
        register_post_type($type, [
            'labels' => [
                'name' => $plural,
                'singular_name' => $singular,
                'add_new' => 'Add New',
                'add_new_item' => 'Add ' . $singular,
                'edit_item' => 'Edit ' . $singular,
                'new_item' => 'New ' . $singular,
                'view_item' => 'Preview ' . $singular,
                'search_items' => 'Search ' . $plural,
                'not_found' => 'No ' . strtolower($plural) . ' found',
                'all_items' => 'All ' . $plural,
            ],
            'public' => $public,
            'show_ui' => true,
            'show_in_rest' => true,
            'supports' => $supports,
            'has_archive' => $public,
            'rewrite' => $public ? ['slug' => strtolower(str_replace('_', '-', $plural))] : false,
            'map_meta_cap' => true,
            'menu_icon' => match ($type) {
                'reunion_memory' => 'dashicons-format-gallery',
                'reunion_suggestion' => 'dashicons-megaphone',
                'reunion_ticket' => 'dashicons-tickets-alt',
                'reunion_component' => 'dashicons-layout',
                'reunion_faq' => 'dashicons-editor-help',
                'reunion_announcement' => 'dashicons-megaphone',
                'reunion_sponsor' => 'dashicons-awards',
                'reunion_tribute' => 'dashicons-heart',
                default => 'dashicons-calendar-alt',
            },
        ]);
    }
}
