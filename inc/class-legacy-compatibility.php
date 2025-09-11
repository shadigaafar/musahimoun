<?php
/**
 * Legacy Compatibility Class
 * 
 * This class handles the transition from old 'musahimoun_' prefixed database keys
 * to new 'mshmn_' prefixed keys while maintaining backward compatibility.
 * 
 * @package Mushahimoun
 * @since 1.0
 */

namespace MSHMN;

if ( ! class_exists( __NAMESPACE__ . '\\Legacy_Compatibility' ) ) :

class Legacy_Compatibility {

    /**
     * Old database option keys
     */
    const OLD_KEYS = array(
        'musahimoun_post_types' => 'mshmn_post_types',
        'musahimoun_author_archive_post_types' => 'mshmn_author_archive_post_types',
        'musahimoun_role_assignments' => 'mshmn_role_assignments',
        'musahimoun_all_post_contributor_ids' => 'mshmn_all_post_contributor_ids',
        'musahimoun_has_set_default_role_assignment' => 'mshmn_has_set_default_role_assignment',
    );

    /**
     * Old post meta keys
     */
    const OLD_POST_META_KEYS = array(
        'musahimoun_role_assignments' => 'mshmn_role_assignments',
        'musahimoun_all_post_contributor_ids' => 'mshmn_all_post_contributor_ids',
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'migrate_legacy_data' ) );
        add_action( 'mshmn_plugin_activated', array( $this, 'migrate_legacy_data' ) );
    }

    /**
     * Migrate legacy data from old keys to new keys
     */
    public function migrate_legacy_data() {
        // Only run migration once
        if ( get_option( 'mshmn_legacy_migration_completed', false ) ) {
            return;
        }

        $this->migrate_options();
        $this->migrate_post_meta();
        
        // Mark migration as completed
        update_option( 'mshmn_legacy_migration_completed', true );
    }

    /**
     * Migrate WordPress options from old keys to new keys
     */
    private function migrate_options() {
        foreach ( self::OLD_KEYS as $old_key => $new_key ) {
            $old_value = get_option( $old_key );
            
            if ( false !== $old_value ) {
                // Only migrate if new key doesn't exist or is empty
                $new_value = get_option( $new_key );
                if ( false === $new_value || empty( $new_value ) ) {
                    update_option( $new_key, $old_value );
                }
                
                // Keep old key for backward compatibility but mark as deprecated
                update_option( $old_key . '_deprecated', true );
            }
        }
    }

    /**
     * Migrate post meta from old keys to new keys
     */
    private function migrate_post_meta() {
        global $wpdb;

        foreach ( self::OLD_POST_META_KEYS as $old_key => $new_key ) {
            // Get all posts with old meta key
            $posts_with_old_meta = array();
            $args = array(
                'post_type'      => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => $old_key,
                        'compare' => 'EXISTS',
                    ),
                ),
            );
            $query = new \WP_Query( $args );
            foreach ( $query->posts as $post_id ) {
                $meta_value = get_post_meta( $post_id, $old_key, true );
                if ( ! empty( $meta_value ) ) {
                    $posts_with_old_meta[] = (object) array(
                        'post_id'   => $post_id,
                        'meta_value'=> $meta_value,
                    );
                }
            }

            foreach ( $posts_with_old_meta as $post_meta ) {
                $post_id = $post_meta->post_id;
                $meta_value = $post_meta->meta_value;
                
                // Only migrate if new meta key doesn't exist
                $existing_new_meta = get_post_meta( $post_id, $new_key, true );
                if ( empty( $existing_new_meta ) ) {
                    update_post_meta( $post_id, $new_key, $meta_value );
                }
            }
        }
    }

    /**
     * Get option value with legacy fallback
     * 
     * @param string $key Option key (new format)
     * @param mixed $default Default value if option doesn't exist
     * @return mixed Option value
     */
    public static function get_option_with_fallback( $key, $default = false ) {
        $value = get_option( $key, $default );
        
        // If new key doesn't exist, try old key
        if ( false === $value ) {
            $old_key = array_search( $key, self::OLD_KEYS );
            if ( $old_key ) {
                $value = get_option( $old_key, $default );
            }
        }
        
        return $value;
    }

    /**
     * Get post meta with legacy fallback
     * 
     * @param int $post_id Post ID
     * @param string $key Meta key (new format)
     * @param bool $single Whether to return a single value
     * @return mixed Meta value
     */
    public static function get_post_meta_with_fallback( $post_id, $key, $single = true ) {
        $value = get_post_meta( $post_id, $key, $single );
        
        // If new key doesn't exist, try old key
        if ( empty( $value ) ) {
            $old_key = array_search( $key, self::OLD_POST_META_KEYS );
            if ( $old_key ) {
                $value = get_post_meta( $post_id, $old_key, $single );
            }
        }
        
        return $value;
    }

    /**
     * Update option and maintain legacy compatibility
     * 
     * @param string $key Option key (new format)
     * @param mixed $value Option value
     * @param string|bool $autoload Whether to autoload the option
     * @return bool|int True on success, false on failure
     */
    public static function update_option_with_legacy( $key, $value, $autoload = null ) {
        $result = update_option( $key, $value, $autoload );
        
        // Also update legacy key for backward compatibility
        $old_key = array_search( $key, self::OLD_KEYS );
        if ( $old_key ) {
            update_option( $old_key, $value, $autoload );
        }
        
        return $result;
    }

    /**
     * Update post meta and maintain legacy compatibility
     * 
     * @param int $post_id Post ID
     * @param string $key Meta key (new format)
     * @param mixed $value Meta value
     * @param mixed $prev_value Previous value
     * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure
     */
    public static function update_post_meta_with_legacy( $post_id, $key, $value, $prev_value = '' ) {
        $result = update_post_meta( $post_id, $key, $value, $prev_value );
        
        // Also update legacy key for backward compatibility
        $old_key = array_search( $key, self::OLD_POST_META_KEYS );
        if ( $old_key ) {
            update_post_meta( $post_id, $old_key, $value, $prev_value );
        }
        
        return $result;
    }
}

endif; 