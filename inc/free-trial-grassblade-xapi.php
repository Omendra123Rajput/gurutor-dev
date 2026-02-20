<?php
/**
 * GrassBlade xAPI Integration
 * 
 * Fetches xAPI statements from GrassBlade LRS and generates personalized study plans
 */

if (!defined('ABSPATH')) exit;


/**
 * GrassBlade xAPI Statement Fetcher for WordPress
 * 
 * This code fetches xAPI statements from GrassBlade LRS
 * Add this to your theme's functions.php file
 */

// Configuration - Store these securely (consider using wp-config.php or environment variables)
define('GRASSBLADE_ENDPOINT', 'https://proven2h7.gblrs.com/xAPI/');
define('GRASSBLADE_API_USER', '13-6942bb4c303e137');
define('GRASSBLADE_API_PASSWORD', 'b5b1fb98b39dfeceab84ab4a0');

/**
 * Fetch xAPI Statements from GrassBlade LRS
 * 
 * @param array $filters Optional filters for the query
 * @return array|WP_Error Array of statements or WP_Error on failure
 */
function grassblade_fetch_statements($filters = array()) {
    $endpoint = GRASSBLADE_ENDPOINT . 'statements';
    
    // Build query parameters
    $query_params = array();
    
    // Filter by agent (learner)
    if (!empty($filters['agent_email'])) {
        $agent = array(
            'mbox' => 'mailto:' . $filters['agent_email']
        );
        $query_params['agent'] = json_encode($agent);
    }
    
    // Filter by activity (lesson/course)
    if (!empty($filters['activity_id'])) {
        $query_params['activity'] = $filters['activity_id'];
    }
    
    // Filter by verb
    if (!empty($filters['verb'])) {
        $query_params['verb'] = $filters['verb'];
    }
    
    // Filter by registration
    if (!empty($filters['registration'])) {
        $query_params['registration'] = $filters['registration'];
    }
    
    // Time-based filters
    if (!empty($filters['since'])) {
        $query_params['since'] = $filters['since'];
    }
    
    if (!empty($filters['until'])) {
        $query_params['until'] = $filters['until'];
    }
    
    // Limit results (0 = server maximum)
    $query_params['limit'] = isset($filters['limit']) ? intval($filters['limit']) : 50;
    
    // Format: ids, exact, or canonical
    $query_params['format'] = isset($filters['format']) ? $filters['format'] : 'exact';
    
    // Sort order
    if (!empty($filters['ascending'])) {
        $query_params['ascending'] = 'true';
    }
    
    // Related activities/agents
    if (!empty($filters['related_activities'])) {
        $query_params['related_activities'] = 'true';
    }
    
    if (!empty($filters['related_agents'])) {
        $query_params['related_agents'] = 'true';
    }
    
    // Build full URL with query string
    if (!empty($query_params)) {
        $endpoint .= '?' . http_build_query($query_params);
    }
    
    // Prepare authentication
    $auth = base64_encode(GRASSBLADE_API_USER . ':' . GRASSBLADE_API_PASSWORD);
    
    // Make the request
    $response = wp_remote_get($endpoint, array(
        'headers' => array(
            'Authorization' => 'Basic ' . $auth,
            'X-Experience-API-Version' => '1.0.3',
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30,
        'sslverify' => true
    ));
    
    // Check for errors
    if (is_wp_error($response)) {
        return new WP_Error('request_failed', 'Failed to connect to LRS: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // Check response code
    if ($response_code !== 200) {
        return new WP_Error('api_error', 'LRS returned error: ' . $response_code . ' - ' . $body);
    }
    
    // Decode JSON
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Failed to parse JSON response: ' . json_last_error_msg());
    }
    
    return $data;
}

/**
 * Fetch statements for a specific user by email
 * 
 * @param string $email User email
 * @param int $limit Number of statements to fetch
 * @return array|WP_Error
 */
function grassblade_get_user_statements($email, $limit = 50) {
    return grassblade_fetch_statements(array(
        'agent_email' => $email,
        'limit' => $limit
    ));
}

/**
 * Fetch statements for a specific activity/lesson
 * 
 * @param string $activity_id Activity IRI
 * @param int $limit Number of statements to fetch
 * @return array|WP_Error
 */
function grassblade_get_activity_statements($activity_id, $limit = 50) {
    return grassblade_fetch_statements(array(
        'activity_id' => $activity_id,
        'limit' => $limit
    ));
}

/**
 * Fetch completed statements for a user
 * 
 * @param string $email User email
 * @param int $limit Number of statements to fetch
 * @return array|WP_Error
 */
function grassblade_get_user_completed_statements($email, $limit = 50) {
    return grassblade_fetch_statements(array(
        'agent_email' => $email,
        'verb' => 'http://adlnet.gov/expapi/verbs/completed',
        'limit' => $limit
    ));
}

/**
 * Get statements since a specific date
 * 
 * @param string $email User email
 * @param string $since ISO 8601 timestamp
 * @param int $limit Number of statements to fetch
 * @return array|WP_Error
 */
function grassblade_get_user_statements_since($email, $since, $limit = 50) {
    return grassblade_fetch_statements(array(
        'agent_email' => $email,
        'since' => $since,
        'limit' => $limit
    ));
}

/**checkpoint1 */


/**
 * Extract bucket data from statement (Test 1 - variables WITHOUT _2 suffix)
 * 
 * @param array $statement Single statement
 * @return array Bucket data (all 20 variables)
 */
function grassblade_extract_bucket_data($statement) {
    // Initialize all 20 variables for Test 1
    $bucket_data = array(
        // Original 4 bucket variables
        'TRN_Bucket' => '',
        'ER_Bucket' => '',
        'DP_Bucket' => '',
        'FPR_Bucket' => '',
        // New Plan variables
        'Plan_DP' => '',
        'Plan_ER' => '',
        'Plan_FPR' => '',
        'Plan_TRN' => '',
        'Plan_Total' => '',
        // New Solve variables
        'Solve_DP' => '',
        'Solve_ER' => '',
        'Solve_FPR' => '',
        'Solve_TRN' => '',
        'Solve_Total' => '',
        // New Understand variables
        'Understand_DP' => '',
        'Understand_ER' => '',
        'Understand_FPR' => '',
        'Understand_TRN' => '',
        'Understand_Total' => ''
    );
    
    // List of all variable keys to extract
    $all_keys = array_keys($bucket_data);
    
    // First, check if extensions exist in result
    if (isset($statement['result']['extensions']) && is_array($statement['result']['extensions'])) {
        foreach ($statement['result']['extensions'] as $key => $value) {
            foreach ($all_keys as $var_key) {
                // Make sure we don't match _2 suffix variables
                if (stripos($key, $var_key) !== false && stripos($key, $var_key . '_2') === false && empty($bucket_data[$var_key])) {
                    $bucket_data[$var_key] = $value;
                }
            }
        }
    }
    
    // Check context extensions
    if (isset($statement['context']['extensions']) && is_array($statement['context']['extensions'])) {
        foreach ($statement['context']['extensions'] as $key => $value) {
            foreach ($all_keys as $var_key) {
                if (stripos($key, $var_key) !== false && stripos($key, $var_key . '_2') === false && empty($bucket_data[$var_key])) {
                    $bucket_data[$var_key] = $value;
                }
            }
        }
    }
    
    // If no bucket data found in extensions, check object ID and name
    // The bucket data is embedded as JSON string in object.id or object.definition.name
    if (empty(array_filter($bucket_data))) {
        $json_string = '';
        
        // Check object ID first
        if (isset($statement['object']['id'])) {
            $object_id = $statement['object']['id'];
            // Extract JSON pattern from the ID (after the URL)
            if (strpos($object_id, '{') !== false) {
                $json_part = substr($object_id, strpos($object_id, '{'));
                if (!empty($json_part)) {
                    $json_string = $json_part;
                }
            }
        }
        
        // If not found in ID, check object name
        if (empty($json_string) && isset($statement['object']['definition']['name']['en-US'])) {
            $object_name = $statement['object']['definition']['name']['en-US'];
            if (strpos($object_name, '{') !== false) {
                $json_string = $object_name;
            }
        }
        
        // Parse the JSON string if found
        if (!empty($json_string)) {
            // Clean up the JSON string
            // Remove trailing comma if exists
            $json_string = rtrim($json_string, ',');
            
            // Ensure it ends with closing brace
            if (substr($json_string, -1) !== '}') {
                $json_string .= '}';
            }
            
            // Try to parse the JSON
            $parsed_data = json_decode($json_string, true);
            
            // If parsing failed, it might be because the response already has the quotes escaped
            // Try to parse it as-is first, then try unescaping if that fails
            if (json_last_error() !== JSON_ERROR_NONE) {
                // The JSON might have escaped quotes - this is already handled by json_decode
                // But let's try one more time with stripslashes in case
                $json_string_clean = stripslashes($json_string);
                $parsed_data = json_decode($json_string_clean, true);
            }
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed_data)) {
                // Extract all variables from parsed JSON (only non-_2 variables)
                foreach ($all_keys as $var_key) {
                    if (isset($parsed_data[$var_key])) {
                        $bucket_data[$var_key] = $parsed_data[$var_key];
                    }
                }
            } else {
                // If JSON parsing still fails, try manual extraction using regex for all variables
                foreach ($all_keys as $var_key) {
                    // Match exact key without _2 suffix
                    if (preg_match('/"' . preg_quote($var_key, '/') . '"\s*:\s*"([^"]*)"/', $json_string, $matches)) {
                        $bucket_data[$var_key] = $matches[1];
                    }
                }
            }
        }
    }
    
    return $bucket_data;
}


/**
 * Extract bucket data from statement (Test 2 - variables WITH _2 suffix)
 * 
 * @param array $statement Single statement
 * @return array Bucket data (all 20 variables for Test 2)
 */
function grassblade_extract_bucket_data_test2($statement) {
    // Initialize all 20 variables for Test 2 (with _2 suffix)
    $bucket_data = array(
        // Original 4 bucket variables
        'TRN_Bucket_2' => '',
        'ER_Bucket_2' => '',
        'DP_Bucket_2' => '',
        'FPR_Bucket_2' => '',
        // Plan variables
        'Plan_DP_2' => '',
        'Plan_ER_2' => '',
        'Plan_FPR_2' => '',
        'Plan_TRN_2' => '',
        'Plan_Total_2' => '',
        // Solve variables
        'Solve_DP_2' => '',
        'Solve_ER_2' => '',
        'Solve_FPR_2' => '',
        'Solve_TRN_2' => '',
        'Solve_Total_2' => '',
        // Understand variables
        'Understand_DP_2' => '',
        'Understand_ER_2' => '',
        'Understand_FPR_2' => '',
        'Understand_TRN_2' => '',
        'Understand_Total_2' => ''
    );
    
    // List of all variable keys to extract
    $all_keys = array_keys($bucket_data);
    
    // First, check if extensions exist in result
    if (isset($statement['result']['extensions']) && is_array($statement['result']['extensions'])) {
        foreach ($statement['result']['extensions'] as $key => $value) {
            foreach ($all_keys as $var_key) {
                if (stripos($key, $var_key) !== false && empty($bucket_data[$var_key])) {
                    $bucket_data[$var_key] = $value;
                }
            }
        }
    }
    
    // Check context extensions
    if (isset($statement['context']['extensions']) && is_array($statement['context']['extensions'])) {
        foreach ($statement['context']['extensions'] as $key => $value) {
            foreach ($all_keys as $var_key) {
                if (stripos($key, $var_key) !== false && empty($bucket_data[$var_key])) {
                    $bucket_data[$var_key] = $value;
                }
            }
        }
    }
    
    // If no bucket data found in extensions, check object ID and name
    if (empty(array_filter($bucket_data))) {
        $json_string = '';
        
        // Check object ID first
        if (isset($statement['object']['id'])) {
            $object_id = $statement['object']['id'];
            if (strpos($object_id, '{') !== false) {
                $json_part = substr($object_id, strpos($object_id, '{'));
                if (!empty($json_part)) {
                    $json_string = $json_part;
                }
            }
        }
        
        // If not found in ID, check object name
        if (empty($json_string) && isset($statement['object']['definition']['name']['en-US'])) {
            $object_name = $statement['object']['definition']['name']['en-US'];
            if (strpos($object_name, '{') !== false) {
                $json_string = $object_name;
            }
        }
        
        // Parse the JSON string if found
        if (!empty($json_string)) {
            $json_string = rtrim($json_string, ',');
            
            if (substr($json_string, -1) !== '}') {
                $json_string .= '}';
            }
            
            $parsed_data = json_decode($json_string, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $json_string_clean = stripslashes($json_string);
                $parsed_data = json_decode($json_string_clean, true);
            }
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed_data)) {
                foreach ($all_keys as $var_key) {
                    if (isset($parsed_data[$var_key])) {
                        $bucket_data[$var_key] = $parsed_data[$var_key];
                    }
                }
            } else {
                foreach ($all_keys as $var_key) {
                    if (preg_match('/"' . preg_quote($var_key, '/') . '"\s*:\s*"([^"]*)"/', $json_string, $matches)) {
                        $bucket_data[$var_key] = $matches[1];
                    }
                }
            }
        }
    }
    
    return $bucket_data;
}


/**
 * Parse and format statement data for easier use
 * 
 * @param array $statements Statement result from API
 * @return array Formatted statements
 */
function grassblade_format_statements($statements) {
    if (is_wp_error($statements)) {
        return $statements;
    }
    
    if (empty($statements['statements']) || !is_array($statements['statements'])) {
        return array();
    }
    
    $formatted = array();
    
    foreach ($statements['statements'] as $statement) {
        // Extract bucket data for both Test 1 and Test 2
        $bucket_data = grassblade_extract_bucket_data($statement);
        $bucket_data_test2 = grassblade_extract_bucket_data_test2($statement);
        
        $formatted[] = array(
            'id' => isset($statement['id']) ? $statement['id'] : '',
            'actor_name' => isset($statement['actor']['name']) ? $statement['actor']['name'] : '',
            'actor_email' => isset($statement['actor']['mbox']) ? str_replace('mailto:', '', $statement['actor']['mbox']) : '',
            'verb_id' => isset($statement['verb']['id']) ? $statement['verb']['id'] : '',
            'verb_display' => isset($statement['verb']['display']['en-US']) ? $statement['verb']['display']['en-US'] : '',
            'object_id' => isset($statement['object']['id']) ? $statement['object']['id'] : '',
            'object_name' => isset($statement['object']['definition']['name']['en-US']) ? $statement['object']['definition']['name']['en-US'] : '',
            'object_type' => isset($statement['object']['definition']['type']) ? $statement['object']['definition']['type'] : '',
            'timestamp' => isset($statement['timestamp']) ? $statement['timestamp'] : '',
            'stored' => isset($statement['stored']) ? $statement['stored'] : '',
            'result' => isset($statement['result']) ? $statement['result'] : null,
            'context' => isset($statement['context']) ? $statement['context'] : null,
            'bucket_data' => $bucket_data,
            'bucket_data_test2' => $bucket_data_test2,
            'raw' => $statement
        );
    }
    
    return $formatted;
}

/**
 * Get bucket data for a specific user - fetches ALL statements (Test 1)
 * 
 * @param string $email User email
 * @return array Array of bucket data with timestamps
 */
function grassblade_get_user_bucket_data($email) {
    $limit = 0;
    
    $statements = grassblade_get_user_statements($email, $limit);
    
    if (is_wp_error($statements)) {
        return $statements;
    }
    
    $formatted = grassblade_format_statements($statements);
    $bucket_results = array();
    
    foreach ($formatted as $stmt) {
        // Only include statements that have Test 1 bucket data (non-_2 variables)
        if (!empty(array_filter($stmt['bucket_data']))) {
            $bucket_results[] = array(
                'timestamp' => $stmt['timestamp'],
                'activity' => $stmt['object_name'],
                'activity_id' => $stmt['object_id'],
                'verb' => $stmt['verb_display'],
                'buckets' => $stmt['bucket_data']
            );
        }
    }
    
    return $bucket_results;
}

/**
 * Get bucket data for a specific user - fetches ALL statements (Test 2)
 * 
 * @param string $email User email
 * @return array Array of bucket data with timestamps
 */
function grassblade_get_user_bucket_data_test2($email) {
    $limit = 0;
    
    $statements = grassblade_get_user_statements($email, $limit);
    
    if (is_wp_error($statements)) {
        return $statements;
    }
    
    $formatted = grassblade_format_statements($statements);
    $bucket_results = array();
    
    foreach ($formatted as $stmt) {
        // Only include statements that have Test 2 bucket data (_2 variables)
        if (!empty(array_filter($stmt['bucket_data_test2']))) {
            $bucket_results[] = array(
                'timestamp' => $stmt['timestamp'],
                'activity' => $stmt['object_name'],
                'activity_id' => $stmt['object_id'],
                'verb' => $stmt['verb_display'],
                'buckets' => $stmt['bucket_data_test2']
            );
        }
    }
    
    return $bucket_results;
}

/**
 * Get the latest bucket data for a user (most recent diagnostic test 1 result)
 * 
 * @param string $email User email
 * @return array|null Latest bucket data or null if not found
 */
function grassblade_get_latest_bucket_data($email) {
    $bucket_data = grassblade_get_user_bucket_data($email);
    
    if (is_wp_error($bucket_data) || empty($bucket_data)) {
        return null;
    }
    
    // Sort by timestamp descending to get the most recent
    usort($bucket_data, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Return the most recent bucket data
    return $bucket_data[0]['buckets'];
}

/**
 * Get the latest bucket data for a user (most recent diagnostic test 2 result)
 * 
 * @param string $email User email
 * @return array|null Latest bucket data or null if not found
 */
function grassblade_get_latest_bucket_data_test2($email) {
    $bucket_data = grassblade_get_user_bucket_data_test2($email);
    
    if (is_wp_error($bucket_data) || empty($bucket_data)) {
        return null;
    }
    
    // Sort by timestamp descending to get the most recent
    usort($bucket_data, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Return the most recent bucket data
    return $bucket_data[0]['buckets'];
}

/**
 * Check if user has completed Test 2 by looking for the "completed" verb in LRS
 * 
 * @param string $email User email
 * @return bool True if user has completed Test 2, false otherwise
 */
function grassblade_check_test2_completion($email) {
    // Query the LRS for user's statements (same approach as bucket data)
    $statements = grassblade_get_user_statements($email, 0);
    
    if (is_wp_error($statements) || empty($statements)) {
        return false;
    }
    
    // Format statements to get verb and object info
    $formatted = grassblade_format_statements($statements);
    
    if (empty($formatted)) {
        return false;
    }
    
    // Look for completed verb with Test 2 object ID
    $completed_verb_id = 'http://adlnet.gov/expapi/verbs/completed';
    $test2_object_id = 'http://www.uniqueurl.com/free-trial-quant-diagnostic-2';
    
    foreach ($formatted as $stmt) {
        // Check if this statement is a completion of Test 2
        if ($stmt['verb_id'] === $completed_verb_id && $stmt['object_id'] === $test2_object_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * AJAX handler to check Test 2 completion status
 */
function ajax_check_test2_completion_status() {
    $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    
    if (empty($user_email)) {
        wp_send_json_error(array('message' => 'Email is required'));
        return;
    }
    
    // Check if user has completed Test 2 (completed verb)
    $has_completed_test2 = grassblade_check_test2_completion($user_email);
    
    // Check if user has bucket data
    $latest_buckets = grassblade_get_latest_bucket_data_test2($user_email);
    $has_bucket_data = !empty($latest_buckets);
    
    // Check if user has purchased a paid plan
    $has_purchased = false;
    
    // Get user by email
    $user = get_user_by('email', $user_email);
    if ($user) {
        $user_id = $user->ID;
        $paid_product_ids = array(7008, 7009);
        
        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status' => array('completed', 'processing', 'on-hold'),
                'limit' => -1,
            ));
            
            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    if (in_array($product_id, $paid_product_ids)) {
                        $has_purchased = true;
                        break 2;
                    }
                }
            }
        }
    }
    
    // All conditions must be met to show button
    $show_button = $has_completed_test2 && $has_bucket_data && $has_purchased;
    
    wp_send_json_success(array(
        'has_completed_test2' => $has_completed_test2,
        'has_bucket_data' => $has_bucket_data,
        'has_purchased' => $has_purchased,
        'show_button' => $show_button
    ));
}
add_action('wp_ajax_check_test2_completion_status', 'ajax_check_test2_completion_status');
add_action('wp_ajax_nopriv_check_test2_completion_status', 'ajax_check_test2_completion_status');

/**
 * Analyze bucket data and categorize topics (Test 1)
 * 
 * @param array $buckets Bucket data array
 * @return array Categorized topics (strengths, weaknesses, low_hanging_fruit)
 */
function grassblade_analyze_buckets($buckets) {
    // Topic to lesson mapping for Test 1
    $topic_mapping = array(
        'TRN_Bucket' => array(
            'topic' => 'Translations',
            'lesson_name' => 'Word Problems Lesson 1',
            'lesson_id' => 4591
        ),
        'ER_Bucket' => array(
            'topic' => 'Exponents & Roots',
            'lesson_name' => 'Algebra Lesson 1',
            'lesson_id' => 4590
        ),
        'DP_Bucket' => array(
            'topic' => 'Divisibility & Primes',
            'lesson_name' => 'Number Properties Lesson 1',
            'lesson_id' => 4592
        ),
        'FPR_Bucket' => array(
            'topic' => 'FPRs',
            'lesson_name' => 'Problem Solving Strategies Lesson 1',
            'lesson_id' => 4589
        )
    );
    
    $analysis = array(
        'strengths' => array(),
        'weaknesses' => array(),
        'low_hanging_fruit' => array()
    );
    
    foreach ($buckets as $bucket_key => $bucket_value) {
        if (isset($topic_mapping[$bucket_key]) && !empty($bucket_value)) {
            $lesson_info = $topic_mapping[$bucket_key];
            
            $value_lower = strtolower(trim($bucket_value));
            
            if ($value_lower === 'strength') {
                $analysis['strengths'][] = $lesson_info;
            } elseif ($value_lower === 'weakness') {
                $analysis['weaknesses'][] = $lesson_info;
            } elseif ($value_lower === 'low-hanging fruit') {
                $analysis['low_hanging_fruit'][] = $lesson_info;
            }
        }
    }
    
    return $analysis;
}

/**
 * Analyze bucket data and categorize topics (Test 2)
 * 
 * @param array $buckets Bucket data array (with _2 suffix)
 * @return array Categorized topics (strengths, weaknesses, low_hanging_fruit)
 */
function grassblade_analyze_buckets_test2($buckets) {
    // Topic to lesson mapping for Test 2
    // For most plans, uses Lesson 1 versions
    $topic_mapping = array(
        'TRN_Bucket_2' => array(
            'topic' => 'Translations',
            'lesson_name' => 'Word Problems Lesson 1',
            'lesson_id' => 4591,
            'lesson_name_l2' => 'Word Problems Lesson 2',
            'lesson_id_l2' => 4601
        ),
        'ER_Bucket_2' => array(
            'topic' => 'Exponents & Roots',
            'lesson_name' => 'Algebra Lesson 1',
            'lesson_id' => 4590,
            'lesson_name_l2' => 'Algebra Lesson 2',
            'lesson_id_l2' => 4600
        ),
        'DP_Bucket_2' => array(
            'topic' => 'Divisibility & Primes',
            'lesson_name' => 'Number Properties Lesson 1',
            'lesson_id' => 4592,
            'lesson_name_l2' => 'Number Properties Lesson 2',
            'lesson_id_l2' => 4602
        ),
        'FPR_Bucket_2' => array(
            'topic' => 'FPRs',
            'lesson_name' => 'Problem Solving Strategies Lesson 1',
            'lesson_id' => 4589,
            'lesson_name_l2' => 'Problem Solving Strategies Lesson 2',
            'lesson_id_l2' => 4599
        )
    );
    
    $analysis = array(
        'strengths' => array(),
        'weaknesses' => array(),
        'low_hanging_fruit' => array()
    );
    
    foreach ($buckets as $bucket_key => $bucket_value) {
        if (isset($topic_mapping[$bucket_key]) && !empty($bucket_value)) {
            $lesson_info = $topic_mapping[$bucket_key];
            
            $value_lower = strtolower(trim($bucket_value));
            
            if ($value_lower === 'strength') {
                $analysis['strengths'][] = $lesson_info;
            } elseif ($value_lower === 'weakness') {
                $analysis['weaknesses'][] = $lesson_info;
            } elseif ($value_lower === 'low-hanging fruit') {
                $analysis['low_hanging_fruit'][] = $lesson_info;
            }
        }
    }
    
    return $analysis;
}

/**
 * Generate study plan based on bucket analysis (Test 1)
 * 
 * @param array $analysis Analyzed bucket data
 * @return array Study plan with priorities
 */
function grassblade_generate_study_plan($analysis) {
    // Define lesson priority order
    $priority_order = array(
        4592, // Number Properties Lesson 1
        4589, // Problem Solving Strategies Lesson 1
        4590, // Algebra Lesson 1
        4591  // Word Problems Lesson 1
    );
    
    // Sort each category by priority
    $sort_by_priority = function($a, $b) use ($priority_order) {
        $pos_a = array_search($a['lesson_id'], $priority_order);
        $pos_b = array_search($b['lesson_id'], $priority_order);
        return $pos_a - $pos_b;
    };
    
    usort($analysis['strengths'], $sort_by_priority);
    usort($analysis['weaknesses'], $sort_by_priority);
    usort($analysis['low_hanging_fruit'], $sort_by_priority);
    
    // Determine study plan type
    $has_strengths = !empty($analysis['strengths']);
    $has_weaknesses = !empty($analysis['weaknesses']);
    $has_low_hanging = !empty($analysis['low_hanging_fruit']);
    
    $study_plan = array(
        'type' => '',
        'priorities' => array()
    );
    
    // Determine study plan type and priorities
    if ($has_strengths && $has_weaknesses && $has_low_hanging) {
        $study_plan['type'] = 'All Three (at least one strength, weakness, and low-hanging fruit)';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Optimizing Strengths for Speed',
                'lessons' => $analysis['strengths']
            ),
            array(
                'title' => 'Picking the Low-Hanging Fruit',
                'lessons' => $analysis['low_hanging_fruit']
            ),
            array(
                'title' => 'Raising the Floor',
                'lessons' => $analysis['weaknesses']
            )
        );
    } elseif ($has_strengths && $has_weaknesses && !$has_low_hanging) {
        $study_plan['type'] = 'Only Strengths and Weaknesses (no low-hanging fruit)';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Optimizing Strengths for Speed',
                'lessons' => $analysis['strengths']
            ),
            array(
                'title' => 'Raising the Floor',
                'lessons' => $analysis['weaknesses']
            )
        );
    } elseif ($has_strengths && !$has_weaknesses && $has_low_hanging) {
        $study_plan['type'] = 'Only Strengths and Low-Hanging Fruit (no weaknesses)';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Optimizing Strengths for Speed',
                'lessons' => $analysis['strengths']
            ),
            array(
                'title' => 'Picking the Low-Hanging Fruit',
                'lessons' => $analysis['low_hanging_fruit']
            )
        );
    } elseif (!$has_strengths && $has_weaknesses && $has_low_hanging) {
        $study_plan['type'] = 'Only Weaknesses and Low-Hanging Fruit (no strengths)';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Picking the Low-Hanging Fruit',
                'lessons' => $analysis['low_hanging_fruit']
            ),
            array(
                'title' => 'Raising the Floor',
                'lessons' => $analysis['weaknesses']
            )
        );
    } elseif ($has_strengths && !$has_weaknesses && !$has_low_hanging) {
        $study_plan['type'] = 'All Strengths';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Master Skills and Strategies that are Unique to the GMAT',
                'lessons' => array_slice($analysis['strengths'], 0, 2)
            ),
            array(
                'title' => 'Learn Some Time-Saving Math Shortcuts (Optional)',
                'lessons' => array_slice($analysis['strengths'], 2)
            )
        );
    } elseif (!$has_strengths && $has_weaknesses && !$has_low_hanging) {
        $study_plan['type'] = 'All Weaknesses';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Build Confidence with the less Math-y Content',
                'lessons' => array_slice($analysis['weaknesses'], 0, 2)
            ),
            array(
                'title' => 'Dusting off those High School Math Skills',
                'lessons' => array_slice($analysis['weaknesses'], 2)
            )
        );
    } elseif (!$has_strengths && !$has_weaknesses && $has_low_hanging) {
        $study_plan['type'] = 'All Low-Hanging Fruit';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Quick Wins',
                'lessons' => array_slice($analysis['low_hanging_fruit'], 0, 2)
            ),
            array(
                'title' => 'Mastering GMAT Decision Making',
                'lessons' => array_slice($analysis['low_hanging_fruit'], 2)
            )
        );
    }
    
    return $study_plan;
}

/**
 * Generate study plan based on bucket analysis (Test 2)
 * 
 * @param array $analysis Analyzed bucket data
 * @return array Study plan with priorities
 */
function grassblade_generate_study_plan_test2($analysis) {
    // Define lesson priority order for Test 2
    $priority_order = array(
        4592, // Number Properties Lesson 1
        4589, // Problem Solving Strategies Lesson 1
        4590, // Algebra Lesson 1
        4591  // Word Problems Lesson 1
    );
    
    // Quant Learning Exercise 1
    $quant_exercise = array(
        'topic' => 'Timed Practice',
        'lesson_name' => 'Quant Learning Exercise 1',
        'lesson_id' => 4595
    );
    
    // FPRs Lesson 1 (Fractions, Percents, and Ratios)
    $fprs_lesson = array(
        'topic' => 'FPRs',
        'lesson_name' => 'Fractions, Percents, and Ratios Lesson 1',
        'lesson_id' => 4603
    );
    
    // Lesson 2 versions for All Strengths plan
    $lesson2_list = array(
        array(
            'topic' => 'Problem Solving Strategies',
            'lesson_name' => 'Problem Solving Strategies Lesson 2',
            'lesson_id' => 4599
        ),
        array(
            'topic' => 'Divisibility & Primes',
            'lesson_name' => 'Number Properties Lesson 2',
            'lesson_id' => 4602
        ),
        array(
            'topic' => 'Exponents & Roots',
            'lesson_name' => 'Algebra Lesson 2',
            'lesson_id' => 4600
        ),
        array(
            'topic' => 'Translations',
            'lesson_name' => 'Word Problems Lesson 2',
            'lesson_id' => 4601
        ),
        $fprs_lesson
    );
    
    // Sort each category by priority
    $sort_by_priority = function($a, $b) use ($priority_order) {
        $pos_a = array_search($a['lesson_id'], $priority_order);
        $pos_b = array_search($b['lesson_id'], $priority_order);
        if ($pos_a === false) $pos_a = 999;
        if ($pos_b === false) $pos_b = 999;
        return $pos_a - $pos_b;
    };
    
    usort($analysis['strengths'], $sort_by_priority);
    usort($analysis['weaknesses'], $sort_by_priority);
    usort($analysis['low_hanging_fruit'], $sort_by_priority);
    
    // Determine study plan type
    $has_strengths = !empty($analysis['strengths']);
    $has_weaknesses = !empty($analysis['weaknesses']);
    $has_low_hanging = !empty($analysis['low_hanging_fruit']);
    
    $study_plan = array(
        'type' => '',
        'priorities' => array()
    );
    
    // Study Plan All Strengths
    if ($has_strengths && !$has_weaknesses && !$has_low_hanging) {
        $study_plan['type'] = 'All Strengths';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Timed Practice w/ Personalized Tutor Support',
                'lessons' => array($quant_exercise),
                'note' => ''
            ),
            array(
                'title' => 'Learning New Skills',
                'lessons' => $lesson2_list,
                'note' => ''
            )
        );
    }
    // Study Plan All Weaknesses
    elseif (!$has_strengths && $has_weaknesses && !$has_low_hanging) {
        $study_plan['type'] = 'All Weaknesses';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Improve Weaknesses',
                'lessons' => $analysis['weaknesses'],
                'note' => '*Be sure to complete the Quant Fundamentals refreshers at the beginning of the lessons above*'
            ),
            array(
                'title' => 'Timed Practice w/ Personalized Tutor Support',
                'lessons' => array($quant_exercise),
                'note' => ''
            )
        );
    }
    // Study Plan All Low-Hanging Fruit
    elseif (!$has_strengths && !$has_weaknesses && $has_low_hanging) {
        $study_plan['type'] = 'All Low-Hanging Fruit';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Turning Low-Hanging Fruit Into Strengths',
                'lessons' => $analysis['low_hanging_fruit'],
                'note' => ''
            ),
            array(
                'title' => 'Timed Practice w/ Personalized Tutor Support',
                'lessons' => array($quant_exercise),
                'note' => ''
            )
        );
    }
    // Study Plan At Least One Weakness & One Low-Hanging Fruit (may or may not have strengths)
    elseif ($has_weaknesses && $has_low_hanging) {
        $study_plan['type'] = 'At Least One Weakness & One Low-Hanging Fruit';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Turning Low-Hanging Fruit Into Strengths',
                'lessons' => $analysis['low_hanging_fruit'],
                'note' => ''
            ),
            array(
                'title' => 'Improving Weaknesses',
                'lessons' => $analysis['weaknesses'],
                'note' => '*Be sure to complete the Quant Fundamentals refreshers at the beginning of the lessons above*'
            ),
            array(
                'title' => 'Timed Practice w/ Personalized Tutor Support',
                'lessons' => array($quant_exercise),
                'note' => ''
            )
        );
    }
    // Study Plan Only Strengths and Weaknesses (no low-hanging fruit)
    elseif ($has_strengths && $has_weaknesses && !$has_low_hanging) {
        $study_plan['type'] = 'Only Strengths and Weaknesses (no low-hanging fruit)';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Improving Weaknesses',
                'lessons' => $analysis['weaknesses'],
                'note' => '*Be sure to complete the Quant Fundamentals refreshers at the beginning of the lessons above*'
            ),
            array(
                'title' => 'Timed Practice w/ Personalized Tutor Support',
                'lessons' => array($quant_exercise),
                'note' => ''
            )
        );
    }
    // Study Plan Only Strengths and Low-Hanging Fruit (no weaknesses)
    elseif ($has_strengths && !$has_weaknesses && $has_low_hanging) {
        $study_plan['type'] = 'Only Strengths and Low-Hanging Fruit (no weaknesses)';
        $study_plan['priorities'] = array(
            array(
                'title' => 'Turning Low-Hanging Fruit Into Strengths',
                'lessons' => $analysis['low_hanging_fruit'],
                'note' => ''
            ),
            array(
                'title' => 'Timed Practice w/ Personalized Tutor Support',
                'lessons' => array($quant_exercise),
                'note' => ''
            )
        );
    }
    
    return $study_plan;
}

/**
 * Display statements in a readable format (for testing)
 * 
 * @param array $statements Statements from API
 */
function grassblade_display_statements($statements) {
    if (is_wp_error($statements)) {
        echo '<div class="error"><p>Error: ' . esc_html($statements->get_error_message()) . '</p></div>';
        return;
    }
    
    $formatted = grassblade_format_statements($statements);
    
    if (empty($formatted)) {
        echo '<p>No statements found.</p>';
        return;
    }
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>Timestamp</th>';
    echo '<th>Actor</th>';
    echo '<th>Verb</th>';
    echo '<th>Object</th>';
    echo '<th>Bucket Data</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach ($formatted as $stmt) {
        echo '<tr>';
        echo '<td>' . esc_html(date('Y-m-d H:i:s', strtotime($stmt['timestamp']))) . '</td>';
        echo '<td>' . esc_html($stmt['actor_name']) . '<br><small>' . esc_html($stmt['actor_email']) . '</small></td>';
        echo '<td>' . esc_html($stmt['verb_display']) . '</td>';
        echo '<td>' . esc_html($stmt['object_name']) . '<br><small>' . esc_html(basename($stmt['object_type'])) . '</small></td>';
        
        // Display bucket data
        echo '<td>';
        $has_bucket_data = false;
        foreach ($stmt['bucket_data'] as $key => $value) {
            if (!empty($value)) {
                $has_bucket_data = true;
                echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '<br>';
            }
        }
        if (!$has_bucket_data) {
            echo '<em style="color: #999;">No bucket data</em>';
        }
        echo '</td>';
        
        echo '</tr>';
    }
    
    echo '</tbody></table>';
}


/**
 * Example: Get statements via AJAX
 */
function grassblade_ajax_get_statements() {
    check_ajax_referer('grassblade_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    
    if (empty($email)) {
        wp_send_json_error('Email is required');
    }
    
    $statements = grassblade_get_user_statements($email);
    
    if (is_wp_error($statements)) {
        wp_send_json_error($statements->get_error_message());
    }
    
    wp_send_json_success(grassblade_format_statements($statements));
}
add_action('wp_ajax_grassblade_get_statements', 'grassblade_ajax_get_statements');

/**
 * Example shortcode to display user's own statements
 * Usage: [grassblade_my_statements]
 */
function grassblade_my_statements_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your activity.</p>';
    }
    
    $current_user = wp_get_current_user();
    $statements = grassblade_get_user_statements($current_user->user_email, 40);
    
    ob_start();
    
    if (is_wp_error($statements)) {
        echo '<p>Unable to load activity data.</p>';
    } else {
        grassblade_display_statements($statements);
    }
    
    return ob_get_clean();
}
add_shortcode('grassblade_my_statements', 'grassblade_my_statements_shortcode');

/**
 * Display personalized study plan based on diagnostic test 1 results
 * Usage: [grassblade_study_plan]
 */
function grassblade_study_plan_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="grassblade-study-plan-notice">
            <p>Please log in to view your personalized study plan.</p>
        </div>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $latest_buckets = grassblade_get_latest_bucket_data($current_user->user_email);
    
    // Check if trial has expired - using LearnDash functions
    global $MY_TRIAL_COURSE_ID;
    $trial_course_id = isset($MY_TRIAL_COURSE_ID) ? intval($MY_TRIAL_COURSE_ID) : 7472;
    
    $trial_expired = false;
    $user_has_access = false;
    
    // Check if user has access to the trial course
    if (function_exists('sfwd_lms_has_access')) {
        $user_has_access = sfwd_lms_has_access($trial_course_id, $user_id);
    }
    
    // Check if trial has expired
    if (function_exists('ld_course_access_expires_on')) {
        $expiry_ts = intval(ld_course_access_expires_on($trial_course_id, $user_id));
        $now_ts = current_time('timestamp');
        
        if ($expiry_ts && $expiry_ts <= $now_ts) {
            $trial_expired = true;
        }
        
        // If no expiry timestamp but user doesn't have access, consider it expired
        if (!$expiry_ts && !$user_has_access) {
            $trial_expired = true;
        }
    }
    
    ob_start();
    
    if (empty($latest_buckets)) {
        echo '<div class="grassblade-study-plan-notice" style="display:none">';
        echo '<h3>📊 Get Your Personalized Study Plan</h3>';
        echo '<p>Complete the diagnostic test to receive a customized study plan based on your strengths and areas for improvement.</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    // Analyze buckets and generate study plan
    $analysis = grassblade_analyze_buckets($latest_buckets);
    $study_plan = grassblade_generate_study_plan($analysis);
    
    if (empty($study_plan['priorities'])) {
        echo '<div class="grassblade-study-plan-notice">';
        echo '<p>Unable to generate study plan. Please complete the diagnostic test.</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    // Display the study plan
    ?>
    <div class="grassblade-study-plan heading">
        
        <h2>🎯 Your Personalized Study Plan</h2>
        <!-- <p class="plan-subtitle">Study Plan Type: <?php echo esc_html($study_plan['type']); ?></p> -->
        
        <?php 
        $priority_counter = 1;
        foreach ($study_plan['priorities'] as $priority): 
            if (empty($priority['lessons'])) continue;
        ?>
            <div class="priority-section">
                <div class="priority-header">
                    <span class="priority-badge">Priority <?php echo $priority_counter; ?></span>
                    <h3 class="priority-title"><?php echo esc_html($priority['title']); ?></h3>
                </div>
                
                <ul class="lesson-list">
                    <?php 
                    $lesson_counter = 1;
                    foreach ($priority['lessons'] as $lesson): 
                        $lesson_url = get_permalink($lesson['lesson_id']);
                        $unique_tooltip_id = 'ld-lesson__row-tooltip--' . $lesson['lesson_id'];
                    ?>
                        <li class="lesson-item <?php echo $trial_expired ? 'lesson-locked' : ''; ?>">
                            <span class="lesson-number"><?php echo $lesson_counter; ?></span>
                            <div class="lesson-info">
                                <p class="lesson-name"><?php echo esc_html($lesson['lesson_name']); ?></p>
                                <p class="lesson-topic">Topic: <?php echo esc_html($lesson['topic']); ?></p>
                            </div>
                            <?php if ($lesson_url): ?>
                                <div class="lesson-link-wrapper">
                                    <?php if ($trial_expired): ?>
                                        <span class="lesson-link disabled">Start Lesson →</span>
                                        <div class="ld-tooltip__text" id="<?php echo esc_attr($unique_tooltip_id); ?>" role="tooltip">
                                            You don't currently have access to this content
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($lesson_url); ?>" class="lesson-link">Start Lesson →</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php 
                        $lesson_counter++;
                    endforeach; 
                    ?>
                </ul>
            </div>
        <?php 
            $priority_counter++;
        endforeach; 
        ?>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('grassblade_study_plan', 'grassblade_study_plan_shortcode');


/**
 * Display personalized study plan based on diagnostic test 2 results
 * Usage: [grassblade_study_plan_test2]
 */
function grassblade_study_plan_test2_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="grassblade-study-plan-notice">
            <p>Please log in to view your personalized study plan.</p>
        </div>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $latest_buckets = grassblade_get_latest_bucket_data_test2($current_user->user_email);
    
    // Check if trial has expired - using LearnDash functions
    global $MY_TRIAL_COURSE_ID;
    $trial_course_id = isset($MY_TRIAL_COURSE_ID) ? intval($MY_TRIAL_COURSE_ID) : 7472;
    
    $trial_expired = false;
    $user_has_access = false;
    
    // Check if user has access to the trial course
    if (function_exists('sfwd_lms_has_access')) {
        $user_has_access = sfwd_lms_has_access($trial_course_id, $user_id);
    }
    
    // Check if trial has expired
    if (function_exists('ld_course_access_expires_on')) {
        $expiry_ts = intval(ld_course_access_expires_on($trial_course_id, $user_id));
        $now_ts = current_time('timestamp');
        
        if ($expiry_ts && $expiry_ts <= $now_ts) {
            $trial_expired = true;
        }
        
        if (!$expiry_ts && !$user_has_access) {
            $trial_expired = true;
        }
    }
    
    ob_start();
    
    if (empty($latest_buckets)) {
        echo '<div class="grassblade-study-plan-notice" style="display:none">';
        echo '<h3>📊 Get Your Personalized Study Plan</h3>';
        echo '<p>Complete the diagnostic test 2 to receive a customized study plan based on your strengths and areas for improvement.</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    // Analyze buckets and generate study plan for Test 2
    $analysis = grassblade_analyze_buckets_test2($latest_buckets);
    $study_plan = grassblade_generate_study_plan_test2($analysis);
    
    if (empty($study_plan['priorities'])) {
        echo '<div class="grassblade-study-plan-notice">';
        echo '<p>Unable to generate study plan. Please complete the diagnostic test 2.</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    // Display the study plan
    ?>
    <div class="grassblade-study-plan heading">
        
        <h2>🎯 Your Personalized Study Plan (Test 2)</h2>
        
        <?php 
        $priority_counter = 1;
        foreach ($study_plan['priorities'] as $priority): 
            if (empty($priority['lessons'])) continue;
        ?>
            <div class="priority-section">
                <div class="priority-header">
                    <span class="priority-badge">Priority <?php echo $priority_counter; ?></span>
                    <h3 class="priority-title"><?php echo esc_html($priority['title']); ?></h3>
                </div>
                
                <?php if (!empty($priority['note'])): ?>
                    <p class="priority-note"><em><?php echo esc_html($priority['note']); ?></em></p>
                <?php endif; ?>
                
                <ul class="lesson-list">
                    <?php 
                    $lesson_counter = 1;
                    foreach ($priority['lessons'] as $lesson): 
                        $lesson_url = get_permalink($lesson['lesson_id']);
                        $unique_tooltip_id = 'ld-lesson__row-tooltip--' . $lesson['lesson_id'];
                    ?>
                        <li class="lesson-item <?php echo $trial_expired ? 'lesson-locked' : ''; ?>">
                            <span class="lesson-number"><?php echo $lesson_counter; ?></span>
                            <div class="lesson-info">
                                <p class="lesson-name"><?php echo esc_html($lesson['lesson_name']); ?></p>
                                <p class="lesson-topic">Topic: <?php echo esc_html($lesson['topic']); ?></p>
                            </div>
                            <?php if ($lesson_url): ?>
                                <div class="lesson-link-wrapper">
                                    <?php if ($trial_expired): ?>
                                        <span class="lesson-link disabled">Start Lesson →</span>
                                        <div class="ld-tooltip__text" id="<?php echo esc_attr($unique_tooltip_id); ?>" role="tooltip">
                                            You don't currently have access to this content
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($lesson_url); ?>" class="lesson-link">Start Lesson →</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php 
                        $lesson_counter++;
                    endforeach; 
                    ?>
                </ul>
            </div>
        <?php 
            $priority_counter++;
        endforeach; 
        ?>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('grassblade_study_plan_test2', 'grassblade_study_plan_test2_shortcode');


/**
 * Handle diagnostic test section and personalized GMAT section visibility and button state
 * Add this code to your functions.php
 */
function grassblade_diagnostic_test_section_handler() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Check if trial has expired
    global $MY_TRIAL_COURSE_ID;
    $trial_course_id = isset($MY_TRIAL_COURSE_ID) ? intval($MY_TRIAL_COURSE_ID) : 7472;
    
    $trial_expired = false;
    $user_has_access = false;
    
    // Check if user has access to the trial course
    if (function_exists('sfwd_lms_has_access')) {
        $user_has_access = sfwd_lms_has_access($trial_course_id, $user_id);
    }
    
    // Check if trial has expired
    if (function_exists('ld_course_access_expires_on')) {
        $expiry_ts = intval(ld_course_access_expires_on($trial_course_id, $user_id));
        $now_ts = current_time('timestamp');
        
        if ($expiry_ts && $expiry_ts <= $now_ts) {
            $trial_expired = true;
        }
        
        // If no expiry timestamp but user doesn't have access, consider it expired
        if (!$expiry_ts && !$user_has_access) {
            $trial_expired = true;
        }
    }
    
    // Check if user has bucket data (completed diagnostic test 1)
    $latest_buckets = grassblade_get_latest_bucket_data($current_user->user_email);
    $has_completed_test = !empty($latest_buckets);
    
    // Check if user has bucket data (completed diagnostic test 2)
    $latest_buckets_test2 = grassblade_get_latest_bucket_data_test2($current_user->user_email);
    $has_completed_test2 = !empty($latest_buckets_test2);
    
    ?>
    <style>
        /* ========================================
           DIAGNOSTIC TEST SECTION (#free-trial-test-1-section)
           ======================================== */
        
        /* Condition 2: Hide entire section if test is completed */
        <?php if ($has_completed_test): ?>
        #free-trial-test-1-section {
            display: none !important;
        }
        <?php endif; ?>
        
        /* Condition 1: Disable button if trial expired and no test completed */
        <?php if ($trial_expired && !$has_completed_test): ?>
        #free-trial-test-1 .elementor-button {
            background-color: #ccc !important;
            color: #666 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.7;
        }
        
        #free-trial-test-1 .elementor-button-wrapper {
            position: relative;
            display: inline-block;
        }
        
        #free-trial-test-1 .elementor-button-wrapper::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            cursor: not-allowed;
        }
        
        #free-trial-test-1 .diagnostic-test-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 10px 16px;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
            z-index: 1000;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        #free-trial-test-1 .diagnostic-test-tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #333;
        }
        
        #free-trial-test-1 .elementor-button-wrapper:hover .diagnostic-test-tooltip {
            visibility: visible;
            opacity: 1;
        }
        <?php endif; ?>
        
        /* ========================================
           PERSONALIZED GMAT SECTION (#personalized-gmatz)
           ======================================== */
        
        /* Condition 2: Hide entire section if test is completed */
        <?php if ($has_completed_test): ?>
        #personalized-gmatz {
            display: none !important;
        }
        <?php endif; ?>
        
        /* Condition 1: Disable button if trial expired and no test completed */
        <?php if ($trial_expired && !$has_completed_test): ?>
        #personalized-gmatz-cta .elementor-button {
            background-color: #ccc !important;
            color: #666 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.7;
        }
        
        #personalized-gmatz-cta .elementor-button-wrapper {
            position: relative;
            display: inline-block;
        }
        
        #personalized-gmatz-cta .elementor-button-wrapper::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            cursor: not-allowed;
        }
        
        #personalized-gmatz-cta .personalized-gmatz-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 10px 16px;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
            z-index: 1000;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        #personalized-gmatz-cta .personalized-gmatz-tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #333;
        }
        
        #personalized-gmatz-cta .elementor-button-wrapper:hover .personalized-gmatz-tooltip {
            visibility: visible;
            opacity: 1;
        }
        <?php endif; ?>
        
        /* ========================================
           DIAGNOSTIC TEST 2 SECTION (#free-trial-test-2-section)
           ======================================== */
        
        /* Hide entire section if test 2 is completed OR if test 1 is NOT completed yet */
        <?php if ($has_completed_test2 || !$has_completed_test): ?>
        #free-trial-test-2-section {
            display: none !important;
        }
        <?php endif; ?>
        
        /* Disable button if trial expired and no test 2 completed */
        <?php if ($trial_expired && !$has_completed_test2): ?>
        #free-trial-test-2 .elementor-button {
            background-color: #ccc !important;
            color: #666 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.7;
        }
        
        #free-trial-test-2 .elementor-button-wrapper {
            position: relative;
            display: inline-block;
        }
        
        #free-trial-test-2 .elementor-button-wrapper::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            cursor: not-allowed;
        }
        
        #free-trial-test-2 .diagnostic-test-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 10px 16px;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
            z-index: 1000;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        #free-trial-test-2 .diagnostic-test-tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #333;
        }
        
        #free-trial-test-2 .elementor-button-wrapper:hover .diagnostic-test-tooltip {
            visibility: visible;
            opacity: 1;
        }
        <?php endif; ?>
        
        /* ========================================
           PERSONALIZED GMAT SECTION 2 (#personalized-gmatz-2)
           ======================================== */
        
        /* Hide entire section if test 2 is completed OR if test 1 is NOT completed yet */
        <?php if ($has_completed_test2 || !$has_completed_test): ?>
        #personalized-gmatz-2 {
            display: none !important;
        }
        <?php endif; ?>
        
        /* Disable button if trial expired and no test 2 completed */
        <?php if ($trial_expired && !$has_completed_test2): ?>
        #personalized-gmatz-cta-test2 .elementor-button {
            background-color: #ccc !important;
            color: #666 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.7;
        }
        
        #personalized-gmatz-cta-test2 .elementor-button-wrapper {
            position: relative;
            display: inline-block;
        }
        
        #personalized-gmatz-cta-test2 .elementor-button-wrapper::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            cursor: not-allowed;
        }
        
        #personalized-gmatz-cta-test2 .personalized-gmatz-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 10px 16px;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
            z-index: 1000;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        #personalized-gmatz-cta-test2 .personalized-gmatz-tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #333;
        }
        
        #personalized-gmatz-cta-test2 .elementor-button-wrapper:hover .personalized-gmatz-tooltip {
            visibility: visible;
            opacity: 1;
        }
        <?php endif; ?>
        
        /* Mobile responsive for tooltips */
        @media (max-width: 768px) {
            #free-trial-test-1 .diagnostic-test-tooltip,
            #free-trial-test-2 .diagnostic-test-tooltip,
            #personalized-gmatz-cta .personalized-gmatz-tooltip,
            #personalized-gmatz-cta-test2 .personalized-gmatz-tooltip {
                white-space: normal;
                max-width: 250px;
                text-align: center;
            }
        }
    </style>
    
    <?php if ($trial_expired && !$has_completed_test): ?>
    <script>
    jQuery(document).ready(function($) {
        // Add tooltip to the diagnostic test button wrapper
        $('#free-trial-test-1 .elementor-button-wrapper').append(
            '<div class="diagnostic-test-tooltip" role="tooltip">Your free trial expired, please upgrade</div>'
        );
        
        // Prevent click on the disabled diagnostic test button
        $('#free-trial-test-1 .elementor-button').on('click', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Add tooltip to the personalized GMAT button wrapper
        $('#personalized-gmatz-cta .elementor-button-wrapper').append(
            '<div class="personalized-gmatz-tooltip" role="tooltip">Your free trial expired, please upgrade</div>'
        );
        
        // Prevent click on the disabled personalized GMAT button
        $('#personalized-gmatz-cta .elementor-button').on('click', function(e) {
            e.preventDefault();
            return false;
        });
    });
    </script>
    <?php endif; ?>
    
    <?php if ($trial_expired && !$has_completed_test2): ?>
    <script>
    jQuery(document).ready(function($) {
        // Add tooltip to the diagnostic test 2 button wrapper
        $('#free-trial-test-2 .elementor-button-wrapper').append(
            '<div class="diagnostic-test-tooltip" role="tooltip">Your free trial expired, please upgrade</div>'
        );
        
        // Prevent click on the disabled diagnostic test 2 button
        $('#free-trial-test-2 .elementor-button').on('click', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Add tooltip to the personalized GMAT 2 button wrapper
        $('#personalized-gmatz-cta-test2 .elementor-button-wrapper').append(
            '<div class="personalized-gmatz-tooltip" role="tooltip">Your free trial expired, please upgrade</div>'
        );
        
        // Prevent click on the disabled personalized GMAT 2 button
        $('#personalized-gmatz-cta-test2 .elementor-button').on('click', function(e) {
            e.preventDefault();
            return false;
        });
    });
    </script>
    <?php endif; ?>
    <?php
}
add_action('wp_footer', 'grassblade_diagnostic_test_section_handler');


/**
 * Method 3: Using JavaScript (most reliable for Elementor pages)
 * Add this to your theme's footer or in Elementor's custom code section
 */

add_action( 'wp_footer', 'custom_ld_breadcrumbs_js_for_free_trial' );

/**
 * Hide Personalized Study Plan 2 tab on paid course page
 * for users who haven't completed Test 2
 * 
 * Shows #personalized-study-plan-2 ONLY if:
 * 1. User has completed Test 2 (has bucket data)
 * 2. AND user has purchased a paid plan (7008 or 7009)
 * 
 * Hides if user has purchased but hasn't completed Test 2
 */
add_action('wp_footer', 'hide_study_plan_2_tab_for_non_test2_users');

function hide_study_plan_2_tab_for_non_test2_users() {
    // Only run on the paid course page (Gurutor's Recommended GMAT Program - ID: 8112)
    if (!is_singular('sfwd-courses')) {
        return;
    }
    
    global $post;
    if ($post->ID !== 8112) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Check if user has Test 2 bucket data (completed Test 2)
    $has_test2_bucket_data = false;
    if (function_exists('grassblade_get_latest_bucket_data_test2')) {
        $latest_buckets_test2 = grassblade_get_latest_bucket_data_test2($current_user->user_email);
        $has_test2_bucket_data = !empty($latest_buckets_test2);
    }
    
    // Check if user has purchased a paid plan
    $has_purchased = false;
    $paid_product_ids = array(7008, 7009);
    
    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ));
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                if (in_array($product_id, $paid_product_ids)) {
                    $has_purchased = true;
                    break 2;
                }
            }
        }
    }
    
    // Show Study Plan 2 tab ONLY if user has BOTH Test 2 data AND has purchased
    // Hide if user has purchased but NO Test 2 data
    $show_study_plan_2 = $has_test2_bucket_data && $has_purchased;
    
    ?>
    <script>
    (function() {
        var showStudyPlan2 = <?php echo $show_study_plan_2 ? 'true' : 'false'; ?>;
        
        function hideStudyPlan2Tab() {
            if (!showStudyPlan2) {
                var studyPlan2Tab = document.getElementById('personalized-study-plan-2');
                if (studyPlan2Tab) {
                    studyPlan2Tab.style.display = 'none';
                }
                
                var testTwoModules = document.querySelectorAll('.test-two-modulez');
                testTwoModules.forEach(function(el) {
                    if (el.id === 'personalized-study-plan-2' || 
                        el.querySelector('.elementor-image-box-title')?.textContent.includes('Personalized Study Plan')) {
                        el.style.display = 'none';
                    }
                });
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', hideStudyPlan2Tab);
        } else {
            hideStudyPlan2Tab();
        }
        
        setTimeout(hideStudyPlan2Tab, 500);
        setTimeout(hideStudyPlan2Tab, 1000);
    })();
    </script>
    <?php
}

function custom_ld_breadcrumbs_js_for_free_trial() {
    
    // Only run on lesson/topic pages
    if ( ! is_singular( array( 'sfwd-lessons', 'sfwd-topic' ) ) ) {
        return;
    }
    
    global $post;
    $current_lesson_id = $post->ID;
    $course_id = learndash_get_course_id( $post->ID );
    
    if ( ! $course_id ) {
        return;
    }
    
    // Course and lesson IDs
    $is_course_9361 = ( $course_id === 9361 ); // Free Trial Copy
    $is_course_8112 = ( $course_id === 8112 ); // Gurutor's Recommended GMAT Program
    $is_course_7472 = ( $course_id === 7472 ); // Gurutor Free Trial
    $is_lesson_9349 = ( $current_lesson_id === 9349 ); // diagnostic test 1 lesson id
    $is_lesson_9412 = ( $current_lesson_id === 9412 ); // diagnostic test 2 lesson id
    
    // Study Plan 2 lesson IDs
    $study_plan_2_lessons = array(
        4592, // Number Properties Lesson 1
        4589, // Problem Solving Strategies Lesson 1
        4590, // Algebra Lesson 1
        4591, // Word Problems Lesson 1
        4595, // Quant Learning Exercise 1
        4603, // Fractions, Percents, and Ratios Lesson 1
        4599, // Problem Solving Strategies Lesson 2
        4602, // Number Properties Lesson 2
        4600, // Algebra Lesson 2
        4601  // Word Problems Lesson 2
    );
    
    // Check if current lesson is a Study Plan 2 lesson
    $is_study_plan_2_lesson = in_array( $current_lesson_id, $study_plan_2_lessons );
    
    // Check if user has active paid subscription
    $user_has_paid_access = false;
    if ( function_exists( 'gurutor_user_has_active_paid_access' ) ) {
        $user_has_paid_access = gurutor_user_has_active_paid_access();
    }
    
    // Show paid course breadcrumb for Study Plan 2 lessons if user has paid access
    $show_paid_breadcrumb_for_study_plan_2 = $is_study_plan_2_lesson && $user_has_paid_access;
    
    // Check if this is a free trial course lesson
    $is_free_trial_course = ( $is_course_7472 || $is_course_9361 );
    
    // Check if we should modify breadcrumbs
    // Only run for: Test lessons, Free Trial courses (9361, 7472), Paid course (8112), or Study Plan 2 lessons for paid users
    if ( ! $is_course_9361 && ! $is_course_7472 && ! $is_lesson_9349 && ! $is_lesson_9412 && ! $is_course_8112 && ! $show_paid_breadcrumb_for_study_plan_2 ) {
        return;
    }
    
    // Get the free trial course URL dynamically
    $free_trial_url = get_permalink( 7472 ); // Course ID: 7472 (Gurutor Free Trial)
    if ( ! $free_trial_url ) {
        $free_trial_url = home_url( '/courses/gurutor-free-trial/' );
    }
    
    // Get the paid course URL dynamically
    $paid_course_url = get_permalink( 8112 ); // Course ID: 8112 (Gurutor's Recommended GMAT Program)
    if ( ! $paid_course_url ) {
        $paid_course_url = home_url( '/courses/gurutors-recommended-gmat-program/' );
    }
    
    // Determine test name for breadcrumb
    $test_name = '';
    if ( $is_lesson_9349 ) {
        $test_name = 'Free Trial – Quant Diagnostic – 1';
    } elseif ( $is_lesson_9412 ) {
        $test_name = 'Free Trial – Quant Diagnostic – 2';
    }
  
    ?>
    <script>
    (function() {
        var isTestLesson = <?php echo ($is_lesson_9349 || $is_lesson_9412) ? 'true' : 'false'; ?>;
        var isCourse9361 = <?php echo $is_course_9361 ? 'true' : 'false'; ?>;
        var isCourse8112 = <?php echo $is_course_8112 ? 'true' : 'false'; ?>;
        var isCourse7472 = <?php echo $is_course_7472 ? 'true' : 'false'; ?>;
        var isFreeTrial = <?php echo $is_free_trial_course ? 'true' : 'false'; ?>;
        var isStudyPlan2Lesson = <?php echo $is_study_plan_2_lesson ? 'true' : 'false'; ?>;
        var userHasPaidAccess = <?php echo $user_has_paid_access ? 'true' : 'false'; ?>;
        var showPaidBreadcrumbForStudyPlan2 = <?php echo $show_paid_breadcrumb_for_study_plan_2 ? 'true' : 'false'; ?>;
        var freeTrialUrl = '<?php echo esc_js( $free_trial_url ); ?>';
        var paidCourseUrl = '<?php echo esc_js( $paid_course_url ); ?>';
        var testName = '<?php echo esc_js( $test_name ); ?>';
        
        function modifyBreadcrumbs() {
            var breadcrumbs = document.querySelector('.ld-breadcrumbs-segments');
            if (!breadcrumbs) return;
            
            if (isTestLesson) {
                var allItems = breadcrumbs.querySelectorAll('li');
                
                allItems.forEach(function(item) {
                    item.style.display = 'none';
                });
                
                var firstItem = breadcrumbs.querySelector('li:first-child');
                if (firstItem) {
                    firstItem.style.display = '';
                    var firstLink = firstItem.querySelector('a');
                    if (!firstLink) {
                        firstLink = document.createElement('a');
                        firstItem.innerHTML = '';
                        firstItem.appendChild(firstLink);
                    }
                    firstLink.textContent = 'Gurutor Free Trial';
                    firstLink.setAttribute('href', freeTrialUrl);
                }
                
                var secondItem = breadcrumbs.querySelector('li:nth-child(2)');
                if (secondItem) {
                    secondItem.style.display = '';
                    secondItem.innerHTML = '<span aria-current="page" style="font-weight: 700;">' + testName + '</span>';
                } else {
                    var newItem = document.createElement('li');
                    newItem.innerHTML = '<span aria-current="page" style="font-weight: 700;">' + testName + '</span>';
                    breadcrumbs.appendChild(newItem);
                }
            } else if (isCourse8112 || showPaidBreadcrumbForStudyPlan2) {
                // Show paid course breadcrumb for Course 8112 OR Study Plan 2 lessons for paid users
                var firstLink = breadcrumbs.querySelector('li:first-child a');
                if (firstLink) {
                    firstLink.textContent = "Gurutor's Recommended GMAT Program";
                    firstLink.setAttribute('href', paidCourseUrl);
                }
            } else if (isFreeTrial) {
                var firstLink = breadcrumbs.querySelector('li:first-child a');
                if (!firstLink) return;
                
                firstLink.textContent = 'Gurutor Free Trial';
                firstLink.setAttribute('href', freeTrialUrl);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', modifyBreadcrumbs);
        } else {
            modifyBreadcrumbs();
        }
        
        // Also run after a short delay to catch any dynamic content
        setTimeout(modifyBreadcrumbs, 500);
        // Run again after 1 second for extra safety
        setTimeout(modifyBreadcrumbs, 1000);
    })();
    </script>
    <?php
}



/**
 * Add "See Study Plan" button after diagnostic test 1 completion
 * Add this to your functions.php
 */
function grassblade_add_study_plan_button() {
    // Only on the diagnostic test lesson page
    if (!is_singular('sfwd-lessons') || get_the_ID() != 9349) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $latest_buckets = grassblade_get_latest_bucket_data($current_user->user_email);
    $has_completed_test = !empty($latest_buckets);
    
    // Get the free trial course URL
    $free_trial_url = get_permalink(7472); // Course ID: 7472 (Gurutor Free Trial)
    if (!$free_trial_url) {
        $free_trial_url = home_url('/courses/gurutor-free-trial/');
    }
    
    ?>
    <style>
        #grassblade-study-plan-button-wrapper {
            display: none;
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        #grassblade-study-plan-button-wrapper.show {
            display: block;
        }
        
        #grassblade-study-plan-button-wrapper .study-plan-message {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
            font-family: "Nunito Sans", Sans-serif;
        }
        
        #grassblade-study-plan-button-wrapper .study-plan-button {
            background-color: #4F80FF;
            font-family: "Nunito Sans", Sans-serif;
            font-weight: 700;
            border-radius: 50px;
            color: #fff;
            display: inline-block;
            font-size: 15px;
            line-height: 1;
            padding: 12px 24px;
            text-align: center;
            transition: all .3s;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(79, 128, 255, 0.3);
        }
        
        #grassblade-study-plan-button-wrapper .study-plan-button:hover {
            background-color: #FBB03B;
            color: #fff;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(251, 176, 59, 0.4);
        }
        
        @media (max-width: 768px) {
            #grassblade-study-plan-button-wrapper .study-plan-message {
                font-size: 16px;
            }
            
            #grassblade-study-plan-button-wrapper .study-plan-button {
                width: 100%;
                padding: 14px 24px;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Check if user has completed test on page load
        var hasCompletedTest = <?php echo $has_completed_test ? 'true' : 'false'; ?>;
        
        if (hasCompletedTest) {
            $('#grassblade-study-plan-button-wrapper').addClass('show');
        }
        
        // Listen for xAPI completion events
        var checkInterval;
        var checkCount = 0;
        var maxChecks = 60; // Check for 5 minutes (60 * 5 seconds)
        
        function checkForCompletion() {
            checkCount++;
            
            // Make AJAX call to check if bucket data exists
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'check_bucket_data_exists',
                    user_email: '<?php echo esc_js($current_user->user_email); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.has_bucket_data) {
                        $('#grassblade-study-plan-button-wrapper').addClass('show');
                        clearInterval(checkInterval);
                    } else if (checkCount >= maxChecks) {
                        clearInterval(checkInterval);
                    }
                }
            });
        }
        
        // Start checking after 5 seconds, then every 5 seconds
        if (!hasCompletedTest) {
            setTimeout(function() {
                checkInterval = setInterval(checkForCompletion, 5000);
            }, 5000);
        }
        
        // Also listen for GrassBlade completion events
        if (typeof window.addEventListener !== 'undefined') {
            window.addEventListener('message', function(event) {
                if (event.data && typeof event.data === 'string') {
                    try {
                        var data = JSON.parse(event.data);
                        if (data.completed === true || data.completion === true) {
                            // Wait 3 seconds for data to be sent to LRS, then show button
                            setTimeout(function() {
                                $('#grassblade-study-plan-button-wrapper').addClass('show');
                            }, 3000);
                        }
                    } catch (e) {
                        // Not JSON, ignore
                    }
                }
            }, false);
        }
    });
    </script>
    
    <?php
    // Add the button HTML after the iframe
    // Pass $has_completed_test to the closure so we can add .show class directly
    add_action('wp_footer', function() use ($free_trial_url, $has_completed_test) {
        $show_class = $has_completed_test ? ' show' : '';
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Insert button wrapper after the GrassBlade iframe
            var iframeWrapper = $('.grassblade').last();
            if (iframeWrapper.length) {
                iframeWrapper.after('<div id="grassblade-study-plan-button-wrapper" class="<?php echo esc_attr(trim($show_class)); ?>">' +
                    '<p class="study-plan-message">🎉 Congratulations! Your diagnostic test is complete.</p>' +
                    '<a href="<?php echo esc_url($free_trial_url); ?>" class="study-plan-button">See Study Plan →</a>' +
                '</div>');
            }
        });
        </script>
        <?php
    });
}
add_action('wp_head', 'grassblade_add_study_plan_button');


/**
 * Add "See Study Plan" button after diagnostic test 2 completion
 * Test 2 Lesson ID: 9412
 * Button only shows when:
 * 1. User has completed Test 2 (bucket data exists)
 * 2. User has purchased a paid package (Product ID 7008 or 7009)
 */
function grassblade_add_study_plan_button_test2() {
    // Only on the diagnostic test 2 lesson page
    if (!is_singular('sfwd-lessons') || get_the_ID() != 9412) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_email = $current_user->user_email;
    
    // Check if user has Test 2 bucket data
    $latest_buckets = grassblade_get_latest_bucket_data_test2($user_email);
    $has_bucket_data = !empty($latest_buckets);
    
    // Check if user has completed Test 2 (completed verb in LRS)
    $has_completed_test2 = grassblade_check_test2_completion($user_email);
    
    // Check if user has purchased one of the paid packages
    // Product IDs: 7008 (Month to Month) or 7009 (6-month Package)
    $paid_product_ids = array(7008, 7009);
    $has_purchased = false;
    
    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing', 'on-hold'),
            'limit' => -1,
        ));
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                if (in_array($product_id, $paid_product_ids)) {
                    $has_purchased = true;
                    break 2;
                }
            }
        }
    }
    
    // Button should only show when ALL conditions are met:
    // 1. User has bucket data (completed test questions)
    // 2. User has purchased a paid package
    // 3. User has completed the full test (completed verb received)
    $show_button = $has_bucket_data && $has_purchased && $has_completed_test2;
    
    // Get the Gurutor's Recommended GMAT Program course URL dynamically
    $recommended_course_url = '';
    
    // Method 1: Try to get course by slug
    $course_query = new WP_Query(array(
        'post_type' => 'sfwd-courses',
        'name' => 'gurutors-recommended-gmat-program',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if ($course_query->have_posts()) {
        $course_query->the_post();
        $recommended_course_url = get_permalink();
        wp_reset_postdata();
    }
    
    // Method 2: Fallback - try using get_page_by_path
    if (empty($recommended_course_url)) {
        $course = get_page_by_path('gurutors-recommended-gmat-program', OBJECT, 'sfwd-courses');
        if ($course) {
            $recommended_course_url = get_permalink($course->ID);
        }
    }
    
    // Method 3: Final fallback - construct URL manually
    if (empty($recommended_course_url)) {
        $recommended_course_url = home_url('/courses/gurutors-recommended-gmat-program/');
    }
    
    ?>
    <style>
        #grassblade-study-plan-button-wrapper-test2 {
            display: none;
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        #grassblade-study-plan-button-wrapper-test2.show {
            display: block;
        }
        
        #grassblade-study-plan-button-wrapper-test2 .study-plan-message {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
            font-family: "Nunito Sans", Sans-serif;
        }
        
        #grassblade-study-plan-button-wrapper-test2 .study-plan-button {
            background-color: #4F80FF;
            font-family: "Nunito Sans", Sans-serif;
            font-weight: 700;
            border-radius: 50px;
            color: #fff;
            display: inline-block;
            font-size: 15px;
            line-height: 1;
            padding: 12px 24px;
            text-align: center;
            transition: all .3s;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(79, 128, 255, 0.3);
        }
        
        #grassblade-study-plan-button-wrapper-test2 .study-plan-button:hover {
            background-color: #FBB03B;
            color: #fff;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(251, 176, 59, 0.4);
        }
        
        @media (max-width: 768px) {
            #grassblade-study-plan-button-wrapper-test2 .study-plan-message {
                font-size: 16px;
            }
            
            #grassblade-study-plan-button-wrapper-test2 .study-plan-button {
                width: 100%;
                padding: 14px 24px;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        var showButtonNow = <?php echo $show_button ? 'true' : 'false'; ?>;
        var buttonShown = false;
        
        function showStudyPlanButton() {
            if (!buttonShown) {
                $('#grassblade-study-plan-button-wrapper-test2').addClass('show');
                buttonShown = true;
            }
        }
        
        if (showButtonNow) {
            setTimeout(showStudyPlanButton, 500);
        }
        
        function checkCompletionAndShowButton() {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'check_test2_completion_status',
                    user_email: '<?php echo esc_js($user_email); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.show_button) {
                        showStudyPlanButton();
                    }
                }
            });
        }
        
        if (!showButtonNow) {
            var checkInterval;
            var checkCount = 0;
            var maxChecks = 720;
            
            setTimeout(function() {
                checkCompletionAndShowButton();
                
                checkInterval = setInterval(function() {
                    checkCount++;
                    
                    if (buttonShown || checkCount >= maxChecks) {
                        clearInterval(checkInterval);
                        return;
                    }
                    
                    checkCompletionAndShowButton();
                }, 5000);
            }, 2000);
        }
    });
    </script>
    
    <?php
    add_action('wp_footer', function() use ($recommended_course_url) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var iframeWrapper = $('.grassblade').last();
            if (iframeWrapper.length) {
                iframeWrapper.after('<div id="grassblade-study-plan-button-wrapper-test2">' +
                    '<p class="study-plan-message">🎉 Congratulations! Your diagnostic test 2 is complete.</p>' +
                    '<a href="<?php echo esc_url($recommended_course_url); ?>" class="study-plan-button">See Study Plan →</a>' +
                '</div>');
            }
        });
        </script>
        <?php
    });
}
add_action('wp_head', 'grassblade_add_study_plan_button_test2');


/**
 * AJAX handler to check if bucket data exists (Test 1)
 */
function ajax_check_bucket_data_exists() {
    if (!isset($_POST['user_email'])) {
        wp_send_json_error(array('message' => 'Email not provided'));
    }
    
    $user_email = sanitize_email($_POST['user_email']);
    $latest_buckets = grassblade_get_latest_bucket_data($user_email);
    $has_bucket_data = !empty($latest_buckets);
    
    wp_send_json_success(array(
        'has_bucket_data' => $has_bucket_data
    ));
}
add_action('wp_ajax_check_bucket_data_exists', 'ajax_check_bucket_data_exists');


/**
 * AJAX handler to check if bucket data exists AND user has purchased (Test 2)
 * Returns true only when both conditions are met:
 * 1. User has bucket data (completed test questions)
 * 2. User has purchased a paid package (Product ID 7008 or 7009)
 */
function ajax_check_bucket_data_exists_test2() {
    if (!isset($_POST['user_email'])) {
        wp_send_json_error(array('message' => 'Email not provided'));
    }
    
    $user_email = sanitize_email($_POST['user_email']);
    
    // Check if bucket data exists
    $latest_buckets = grassblade_get_latest_bucket_data_test2($user_email);
    $has_bucket_data = !empty($latest_buckets);
    
    // Check if user has purchased one of the paid packages
    $has_purchased = false;
    
    if (is_user_logged_in() && function_exists('wc_get_orders')) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Product IDs: 7008 (Month to Month) or 7009 (6-month Package)
        $paid_product_ids = array(7008, 7009);
        
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ));
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                if (in_array($product_id, $paid_product_ids)) {
                    $has_purchased = true;
                    break 2;
                }
            }
        }
    }
    
    // Both conditions must be true
    $show_button = $has_bucket_data && $has_purchased;
    
    wp_send_json_success(array(
        'has_bucket_data' => $show_button
    ));
}
add_action('wp_ajax_check_bucket_data_exists_test2', 'ajax_check_bucket_data_exists_test2');


/***********************/


/**
 * FIXED: Inject bucket data into Lectora Test 2
 * 
 * 
 * ISSUES FIXED:
 * 1. Timing issue - data was being set after Lectora tried to read it
 * 2. iframe context issue - window.top may not be accessible from iframe
 * 3. Added multiple fallback methods for variable injection
 */

/**
 * Inject bucket data into Lectora Test 2
 * This makes the bucket data available to the Lectora module
 */
function inject_bucket_data_to_lectora() {
    // Only run on the Test 2 lesson page
    // Test 2 Lesson ID: 9412
    // if (!is_singular('sfwd-lessons') || get_the_ID() != 9412 ) {
    //     return;
    // }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $latest_buckets = grassblade_get_latest_bucket_data($current_user->user_email);
    
    // If no bucket data found, return
    if (empty($latest_buckets)) {
        return;
    }
    
    // Prepare the bucket data for JavaScript
    $bucket_json = json_encode($latest_buckets);
    
    ?>
    <script>
    (function() {
        if (!window.top.lectoraModuleVars) {
            window.top.lectoraModuleVars = {};
        }
        
        var bucketData = <?php echo $bucket_json; ?>;
        
        // Original 4 bucket variables
        window.top.lectoraModuleVars.TRN_Bucket = bucketData.TRN_Bucket || '';
        window.top.lectoraModuleVars.ER_Bucket = bucketData.ER_Bucket || '';
        window.top.lectoraModuleVars.DP_Bucket = bucketData.DP_Bucket || '';
        window.top.lectoraModuleVars.FPR_Bucket = bucketData.FPR_Bucket || '';
        
        // Plan variables
        window.top.lectoraModuleVars.Plan_DP = bucketData.Plan_DP || '';
        window.top.lectoraModuleVars.Plan_ER = bucketData.Plan_ER || '';
        window.top.lectoraModuleVars.Plan_FPR = bucketData.Plan_FPR || '';
        window.top.lectoraModuleVars.Plan_TRN = bucketData.Plan_TRN || '';
        window.top.lectoraModuleVars.Plan_Total = bucketData.Plan_Total || '';
        
        // Solve variables
        window.top.lectoraModuleVars.Solve_DP = bucketData.Solve_DP || '';
        window.top.lectoraModuleVars.Solve_ER = bucketData.Solve_ER || '';
        window.top.lectoraModuleVars.Solve_FPR = bucketData.Solve_FPR || '';
        window.top.lectoraModuleVars.Solve_TRN = bucketData.Solve_TRN || '';
        window.top.lectoraModuleVars.Solve_Total = bucketData.Solve_Total || '';
        
        // Understand variables
        window.top.lectoraModuleVars.Understand_DP = bucketData.Understand_DP || '';
        window.top.lectoraModuleVars.Understand_ER = bucketData.Understand_ER || '';
        window.top.lectoraModuleVars.Understand_FPR = bucketData.Understand_FPR || '';
        window.top.lectoraModuleVars.Understand_TRN = bucketData.Understand_TRN || '';
        window.top.lectoraModuleVars.Understand_Total = bucketData.Understand_Total || '';
        
        // Also set on current window as fallback
        try {
            if (!window.lectoraModuleVars) {
                window.lectoraModuleVars = {};
            }
            Object.keys(window.top.lectoraModuleVars).forEach(function(key) {
                window.lectoraModuleVars[key] = window.top.lectoraModuleVars[key];
            });
        } catch(e) {}
        
        // Store in sessionStorage for cross-frame access
        try {
            sessionStorage.setItem('lectoraBucketData', JSON.stringify(bucketData));
        } catch(e) {}
        
        // Create a global function that Lectora can call
        window.getLectoraBucketData = function() {
            return bucketData;
        };
        
        // Use postMessage to communicate with iframe
        function injectIntoIframe() {
            var iframes = document.querySelectorAll('iframe');
            
            iframes.forEach(function(iframe) {
                try {
                    if (iframe.contentWindow) {
                        iframe.contentWindow.lectoraModuleVars = iframe.contentWindow.lectoraModuleVars || {};
                        Object.keys(bucketData).forEach(function(key) {
                            iframe.contentWindow.lectoraModuleVars[key] = bucketData[key] || '';
                        });
                    }
                } catch(e) {
                    try {
                        iframe.contentWindow.postMessage({
                            type: 'lectoraBucketData',
                            data: bucketData
                        }, '*');
                    } catch(e2) {}
                }
            });
        }
        
        injectIntoIframe();
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                injectIntoIframe();
            });
        }
        
        window.addEventListener('load', function() {
            injectIntoIframe();
            setTimeout(injectIntoIframe, 1000);
            setTimeout(injectIntoIframe, 2000);
            setTimeout(injectIntoIframe, 3000);
        });
        
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.tagName === 'IFRAME') {
                        setTimeout(function() {
                            injectIntoIframe();
                        }, 500);
                    }
                });
            });
        });
        
        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        }
    })();
    </script>
    <?php
}
add_action('wp_head', 'inject_bucket_data_to_lectora', 5);

/**
 * ============================================================================
 * PAID USER REDIRECT & FREE TRIAL CTA HANDLING
 * ============================================================================
 * 
 * These functions handle:
 * 1. Redirecting paid users from free trial pages to paid course
 * 2. Disabling "Start Free Trial" CTAs for paid users
 */

/**
 * Check if user has an active paid subscription or has purchased a paid plan
 * 
 * @param int $user_id User ID (optional, defaults to current user)
 * @return bool True if user has active paid access, false otherwise
 */
function gurutor_user_has_active_paid_access($user_id = null) {
    if ($user_id === null) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_id = get_current_user_id();
    }
    
    $paid_product_ids = array(7008, 7009); // Month to Month and 6-month Package
    
    // ONLY check WooCommerce Subscriptions for ACTIVE subscriptions
    // Do NOT check past orders - expired subscriptions should not count
    if (function_exists('wcs_get_users_subscriptions')) {
        $subscriptions = wcs_get_users_subscriptions($user_id);
        
        foreach ($subscriptions as $subscription) {
            // Only check for truly active subscriptions
            // 'active' = currently active and billing
            // 'pending-cancel' = active but will cancel at end of period (still has access)
            if ($subscription->has_status(array('active', 'pending-cancel'))) {
                // Check if subscription contains paid products
                foreach ($subscription->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    if (in_array($product_id, $paid_product_ids)) {
                        return true;
                    }
                }
            }
        }
    }
    
    // If WooCommerce Subscriptions is not available, return false
    // We don't want to check orders because that would include expired subscriptions
    return false;
}


/**
 * Get the active subscription product ID for the user
 * 
 * @param int $user_id User ID (optional, defaults to current user)
 * @return int|null Product ID (7008 or 7009) if active, null otherwise
 */
function gurutor_get_active_subscription_product_id($user_id = null) {
    if ($user_id === null) {
        if (!is_user_logged_in()) {
            return null;
        }
        $user_id = get_current_user_id();
    }
    
    $paid_product_ids = array(7008, 7009); // Month to Month and 6-month Package
    
    // Check WooCommerce Subscriptions for ACTIVE subscriptions
    if (function_exists('wcs_get_users_subscriptions')) {
        $subscriptions = wcs_get_users_subscriptions($user_id);
        
        foreach ($subscriptions as $subscription) {
            // Only check for truly active subscriptions
            if ($subscription->has_status(array('active', 'pending-cancel'))) {
                foreach ($subscription->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    if (in_array($product_id, $paid_product_ids)) {
                        return $product_id;
                    }
                }
            }
        }
    }
    
    return null;
}


/**
 * Disable pricing card CTAs based on user's active subscription
 * 
 * - If user has monthly plan (7008): Disable global-monthly-cta and global-free-cta
 * - If user has 6-month plan (7009): Disable global-monthly-cta, global-6-monthly-cta, and global-free-cta
 * - If subscription expired/cancelled: All CTAs remain enabled
 */
function gurutor_disable_pricing_ctas_for_paid_users() {
    // Only run if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Get active subscription product ID
    $active_product_id = gurutor_get_active_subscription_product_id();
    
    // If no active subscription, don't disable anything
    if ($active_product_id === null) {
        return;
    }
    
    // Determine which CTAs to disable based on active product
    $disable_monthly = false;
    $disable_6monthly = false;
    $disable_free = false;
    $tooltip_message = '';
    
    if ($active_product_id === 7008) {
        // Monthly plan active - disable monthly and free trial
        $disable_monthly = true;
        $disable_free = true;
        $tooltip_message = 'You already have an active monthly subscription.';
    } elseif ($active_product_id === 7009) {
        // 6-month plan active - disable all three
        $disable_monthly = true;
        $disable_6monthly = true;
        $disable_free = true;
        $tooltip_message = 'You already have an active 6-month subscription.';
    }
    
    ?>
    <style>
    /* Disable Pricing CTAs for paid users */
    <?php if ($disable_monthly) : ?>
    .global-monthly-cta {
        position: relative;
    }
    
    .global-monthly-cta .elementor-button,
    .global-monthly-cta .elementor-widget-container a,
    .global-monthly-cta .elementor-button-wrapper a,
    .global-monthly-cta a.product_type_subscription,
    .global-monthly-cta a {
        pointer-events: none !important;
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }
    <?php endif; ?>
    
    <?php if ($disable_6monthly) : ?>
    .global-6-monthly-cta {
        position: relative;
    }
    
    .global-6-monthly-cta .elementor-button,
    .global-6-monthly-cta .elementor-widget-container a,
    .global-6-monthly-cta .elementor-button-wrapper a,
    .global-6-monthly-cta a.product_type_subscription,
    .global-6-monthly-cta a {
        pointer-events: none !important;
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }
    <?php endif; ?>
    
    <?php if ($disable_free) : ?>
    .global-free-cta {
        position: relative;
    }
    
    .global-free-cta .elementor-button,
    .global-free-cta .elementor-widget-container a,
    .global-free-cta .elementor-button-wrapper a,
    .global-free-cta a.product_type_subscription,
    .global-free-cta a {
        pointer-events: none !important;
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }
    <?php endif; ?>
    
    /* Pricing Tooltip styling */
    .gurutor-pricing-tooltip {
        position: absolute;
        bottom: 100%;
        /*left: 50%;
        transform: translateX(-50%);*/
        background-color: #333;
        color: #fff;
        padding: 10px 14px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
        z-index: 9999;
        margin-bottom: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        text-align: center;
        line-height: 1.5;
    }
    
    .gurutor-pricing-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: #333;
    }
    
    .global-monthly-cta:hover .gurutor-pricing-tooltip,
    .global-6-monthly-cta:hover .gurutor-pricing-tooltip,
    .global-free-cta:hover .gurutor-pricing-tooltip {
        opacity: 1;
        visibility: visible;
    }
    
    /* Mobile responsive tooltip */
    @media (max-width: 768px) {
        .gurutor-pricing-tooltip {
            white-space: normal;
            width: 220px;
        }
    }
    </style>
    
    <script>
    (function() {
        var disableMonthly = <?php echo $disable_monthly ? 'true' : 'false'; ?>;
        var disable6Monthly = <?php echo $disable_6monthly ? 'true' : 'false'; ?>;
        var disableFree = <?php echo $disable_free ? 'true' : 'false'; ?>;
        var tooltipMessage = '<?php echo esc_js($tooltip_message); ?>';
        
        function disablePricingCTAs() {
            var ctaSelectors = [];
            
            if (disableMonthly) {
                ctaSelectors.push('.global-monthly-cta');
            }
            if (disable6Monthly) {
                ctaSelectors.push('.global-6-monthly-cta');
            }
            if (disableFree) {
                ctaSelectors.push('.global-free-cta');
            }
            
            if (ctaSelectors.length === 0) {
                return;
            }
            
            var ctaElements = document.querySelectorAll(ctaSelectors.join(', '));
            
            ctaElements.forEach(function(cta) {
                // Skip if already processed
                if (cta.getAttribute('data-pricing-disabled') === 'true') {
                    return;
                }
                
                // Mark as disabled
                cta.setAttribute('data-pricing-disabled', 'true');
                
                // Create tooltip element (simple message only)
                var tooltip = document.createElement('div');
                tooltip.className = 'gurutor-pricing-tooltip';
                tooltip.innerHTML = tooltipMessage;
                cta.appendChild(tooltip);
                
                // Find all links and buttons within
                var links = cta.querySelectorAll('a, button');
                links.forEach(function(link) {
                    // Prevent click
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                    
                    // Remove href to prevent navigation
                    if (link.hasAttribute('href')) {
                        link.setAttribute('data-original-href', link.getAttribute('href'));
                        link.setAttribute('href', 'javascript:void(0);');
                    }
                    
                    // Add disabled attribute
                    link.setAttribute('disabled', 'disabled');
                    link.setAttribute('aria-disabled', 'true');
                    
                    // Remove WooCommerce AJAX add to cart class
                    link.classList.remove('ajax_add_to_cart');
                    link.classList.remove('add_to_cart_button');
                });
            });
        }
        
        // Run on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', disablePricingCTAs);
        } else {
            disablePricingCTAs();
        }
        
        // Run after load to catch dynamically loaded content
        window.addEventListener('load', function() {
            disablePricingCTAs();
            setTimeout(disablePricingCTAs, 500);
            setTimeout(disablePricingCTAs, 1000);
        });
        
        // Watch for dynamically added CTAs
        var observer = new MutationObserver(function(mutations) {
            disablePricingCTAs();
        });
        
        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        }
    })();
    </script>
    <?php
}
add_action('wp_head', 'gurutor_disable_pricing_ctas_for_paid_users', 99);


/**
 * Redirect paid users from free trial pages to paid course
 * 
 * Redirects users with active paid subscriptions from:
 * - Free Trial course page (/courses/gurutor-free-trial/)
 * - My Account with type_subs=free parameter
 * 
 * To the paid course: /courses/gurutors-recommended-gmat-program/
 */
function gurutor_redirect_paid_users_from_free_trial() {
    // Don't redirect in admin area
    if (is_admin()) {
        return;
    }
    
    // Don't redirect AJAX requests
    if (wp_doing_ajax()) {
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Check if user has active paid access
    if (!gurutor_user_has_active_paid_access()) {
        return;
    }
    
    // Get paid course URL
    $paid_course_url = home_url('/courses/gurutors-recommended-gmat-program/');
    
    // Try to get the actual permalink
    $paid_course = get_page_by_path('gurutors-recommended-gmat-program', OBJECT, 'sfwd-courses');
    if ($paid_course) {
        $paid_course_url = get_permalink($paid_course->ID);
    }
    
    $should_redirect = false;
    
    // Check 1: Free Trial course page (Course ID 7472 or 9361)
    if (is_singular('sfwd-courses')) {
        $current_course_id = get_the_ID();
        $free_trial_course_ids = array(7472, 9361); // Free Trial and Free Trial Copy
        
        if (in_array($current_course_id, $free_trial_course_ids)) {
            $should_redirect = true;
        }
    }
    
    // Check 2: Free Trial course by URL slug
    if (!$should_redirect) {
        $current_url = $_SERVER['REQUEST_URI'];
        if (strpos($current_url, '/courses/gurutor-free-trial') !== false) {
            $should_redirect = true;
        }
    }
    
    // Check 3: My Account page with type_subs=free parameter
    if (!$should_redirect) {
        if (function_exists('is_account_page') && is_account_page()) {
            if (isset($_GET['type_subs']) && $_GET['type_subs'] === 'free') {
                $should_redirect = true;
            }
        }
    }
    
    // Check 4: Direct URL check for my-account with type_subs=free
    if (!$should_redirect) {
        $current_url = $_SERVER['REQUEST_URI'];
        if (strpos($current_url, 'my-account') !== false && strpos($current_url, 'type_subs=free') !== false) {
            $should_redirect = true;
        }
    }
    
    // Perform redirect if needed
    if ($should_redirect) {
        wp_redirect($paid_course_url);
        exit;
    }
}
add_action('template_redirect', 'gurutor_redirect_paid_users_from_free_trial', 5);


/**
 * Disable "Start Free Trial" CTAs for paid users
 * 
 * Adds CSS/JS to disable buttons with class 'start-free-trial-cta' 
 * and show a tooltip with clickable "My Courses" link
 */
function gurutor_disable_free_trial_cta_for_paid_users() {
    // Only run if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Check if user has active paid access
    if (!gurutor_user_has_active_paid_access()) {
        return;
    }
    
    // Get My Courses URL
    $my_courses_url = home_url('/courses/gurutors-recommended-gmat-program/?type=check_course');
    
    ?>
    <style>
    /* Disable Free Trial CTAs for paid users */
    .start-free-trial-cta {
        position: relative;
    }
    
    .start-free-trial-cta .elementor-button,
    .start-free-trial-cta a:not(.gurutor-tooltip-link) {
        pointer-events: none !important;
        opacity: 0.6 !important;
        cursor: not-allowed !important;
        position: relative;
    }
    
    /* HTML Tooltip styling */
    .gurutor-paid-tooltip {
        position: absolute;
        bottom: 100%;
        /*
        left: 50%;
        transform: translateX(-50%); */
        background-color: #333;
        color: #fff;
        padding: 10px 14px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
        z-index: 9999;
        margin-bottom: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        text-align: center;
        line-height: 1.5;
    }
    
    .gurutor-paid-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        
        left: 50%;
        transform: translateX(-50%); 
        border: 6px solid transparent;
        border-top-color: #333;
    }
    
    .gurutor-paid-tooltip .gurutor-tooltip-link {
        color: #4F80FF !important;
        text-decoration: underline !important;
        pointer-events: auto !important;
        cursor: pointer !important;
        font-weight: 600 !important;
        /* Reset inherited styles */
        background-color: transparent !important;
        background: none !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        display: inline !important;
        font-size: inherit !important;
        font-family: inherit !important;
        box-shadow: none !important;
        fill: none !important;
        transition: color 0.3s !important;
        line-height: inherit !important;
        text-align: left !important;
        width: auto !important;
        height: auto !important;
        min-width: 0 !important;
        min-height: 0 !important;
        max-width: none !important;
        max-height: none !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: none !important;
        position: static !important;
        float: none !important;
        clear: none !important;
        vertical-align: baseline !important;
        letter-spacing: normal !important;
        word-spacing: normal !important;
        text-transform: none !important;
        text-indent: 0 !important;
        text-shadow: none !important;
        white-space: normal !important;
        outline: none !important;
    }
    
    /* Extra specificity for stubborn external styles */
    .start-free-trial-cta .gurutor-paid-tooltip .gurutor-tooltip-link,
    #starz-free .gurutor-paid-tooltip .gurutor-tooltip-link,
    div.gurutor-paid-tooltip a.gurutor-tooltip-link {
        color: #4F80FF !important;
        text-decoration: underline !important;
        background-color: transparent !important;
        background: none !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        display: inline !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        box-shadow: none !important;
    }
    
    .gurutor-paid-tooltip .gurutor-tooltip-link:hover {
        color: #FBB03B !important;
        background-color: transparent !important;
        background: none !important;
    }
    
    .start-free-trial-cta .gurutor-paid-tooltip .gurutor-tooltip-link:hover,
    #starz-free .gurutor-paid-tooltip .gurutor-tooltip-link:hover,
    div.gurutor-paid-tooltip a.gurutor-tooltip-link:hover {
        color: #FBB03B !important;
        background-color: transparent !important;
        background: none !important;
    }
    
    .start-free-trial-cta:hover .gurutor-paid-tooltip {
        opacity: 1;
        visibility: visible;
    }
    
    /* Mobile responsive tooltip */
    @media (max-width: 768px) {
        .gurutor-paid-tooltip {
            white-space: normal;
            width: 220px;
        }
    }
    </style>
    
    <script>
    (function() {
        var myCoursesUrl = '<?php echo esc_js($my_courses_url); ?>';
        
        function disableFreeTrialCTAs() {
            var ctaElements = document.querySelectorAll('.start-free-trial-cta');
            
            ctaElements.forEach(function(cta) {
                // Skip if already processed
                if (cta.getAttribute('data-paid-user') === 'true') {
                    return;
                }
                
                // Mark as disabled
                cta.setAttribute('data-paid-user', 'true');
                
                // Create tooltip element with clickable link
                var tooltip = document.createElement('div');
                tooltip.className = 'gurutor-paid-tooltip';
                tooltip.innerHTML = 'You already have an active subscription.<br>Go to <a href="' + myCoursesUrl + '" class="gurutor-tooltip-link">My Courses</a> instead.';
                cta.appendChild(tooltip);
                
                // Find all links and buttons within (except tooltip link)
                var links = cta.querySelectorAll('a:not(.gurutor-tooltip-link), button');
                links.forEach(function(link) {
                    // Prevent click
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                    
                    // Remove href to prevent navigation
                    if (link.hasAttribute('href')) {
                        link.setAttribute('data-original-href', link.getAttribute('href'));
                        link.setAttribute('href', 'javascript:void(0);');
                    }
                    
                    // Add disabled attribute
                    link.setAttribute('disabled', 'disabled');
                    link.setAttribute('aria-disabled', 'true');
                });
            });
        }
        
        // Run on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', disableFreeTrialCTAs);
        } else {
            disableFreeTrialCTAs();
        }
        
        // Run after load to catch dynamically loaded content
        window.addEventListener('load', function() {
            disableFreeTrialCTAs();
            setTimeout(disableFreeTrialCTAs, 500);
            setTimeout(disableFreeTrialCTAs, 1000);
        });
        
        // Watch for dynamically added CTAs
        var observer = new MutationObserver(function(mutations) {
            disableFreeTrialCTAs();
        });
        
        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        }
    })();
    </script>
    <?php
}
add_action('wp_head', 'gurutor_disable_free_trial_cta_for_paid_users', 99);


/**
 * Also handle the redirect via JavaScript for SPA-like navigation
 * This catches cases where template_redirect might not fire
 */
function gurutor_js_redirect_for_paid_users() {
    // Only run if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Check if user has active paid access
    if (!gurutor_user_has_active_paid_access()) {
        return;
    }
    
    // Get paid course URL
    $paid_course_url = home_url('/courses/gurutors-recommended-gmat-program/');
    $paid_course = get_page_by_path('gurutors-recommended-gmat-program', OBJECT, 'sfwd-courses');
    if ($paid_course) {
        $paid_course_url = get_permalink($paid_course->ID);
    }
    
    ?>
    <script>
    (function() {
        var paidCourseUrl = '<?php echo esc_js($paid_course_url); ?>';
        
        function checkAndRedirect() {
            var currentUrl = window.location.href;
            var currentPath = window.location.pathname;
            var searchParams = window.location.search;
            
            var shouldRedirect = false;
            
            // Check for free trial course URL
            if (currentPath.indexOf('/courses/gurutor-free-trial') !== -1) {
                shouldRedirect = true;
            }
            
            // Check for my-account with type_subs=free
            if (currentPath.indexOf('/my-account') !== -1 && searchParams.indexOf('type_subs=free') !== -1) {
                shouldRedirect = true;
            }
            
            if (shouldRedirect) {
                window.location.replace(paidCourseUrl);
            }
        }
        
        // Run check on page load
        checkAndRedirect();
        
        // Also watch for URL changes (for SPA navigation)
        if (typeof window.history.pushState === 'function') {
            var originalPushState = history.pushState;
            history.pushState = function() {
                originalPushState.apply(history, arguments);
                setTimeout(checkAndRedirect, 100);
            };
            
            window.addEventListener('popstate', function() {
                setTimeout(checkAndRedirect, 100);
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'gurutor_js_redirect_for_paid_users', 99);