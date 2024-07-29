<?php
/*
Template Name: Create AutoPost
*/
class DailyPostCreator
{
    public function create_daily_post()
    {
        try {
            $api_key = 'sk-proj-02S7EV8RN3NcJ2BFMhBzT3BlbkFJ3bLAESvG3S8TyExCcPtz'; // Replace with your OpenAI API key
            $post_data = $this->generate_post_data($api_key);
            echo "<pre>";
            var_dump($post_data);
            if ($post_data) {
                $post_id = wp_insert_post(array(
                    'post_title' => $post_data['title'],
                    'post_content' => $post_data['content'],
                    'post_status' => 'publish',
                    'post_author' => 1, // Change to the desired author ID
                    'post_category' => array(90), // Change to the desired category ID
                    'tags_input' => $post_data['tags']
                ));

                if ($post_id && !is_wp_error($post_id)) {
                    // Generate and set the featured image
                    $image_url = $this->generate_featured_image($api_key, $post_data['title']);
                    echo "<pre>";
                    var_dump($image_url);
                    if ($image_url) {
                        $check = $this->set_featured_image($image_url, $post_id,$post_data['title']);
                        echo "Image fill testing";
                        echo "<pre>";
                        var_dump($check);
                    }
                } else {
                    return new WP_Error('post_creation_failed', 'Failed to create post');
                }
            } else {
                return new WP_Error('post_data_generation_failed', 'Failed to generate post data');
            }

            return $post_id;
        } catch (\Throwable $th) {
            return new WP_Error('unexpected_error', $th->getMessage());
        }
    }

    private function generate_post_data($api_key)
    {
        try {
            $prompt = "Generate a blog post of crypto adventure with  title, content, and FAQs. i used preg_match (/Title:(.*?)Content:(.*?)FAQs:(.*)/s) to Extract title, content, and FAQs from the response";

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'timeout' => 50,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-4o',
                    'messages' => array(
                        array('role' => 'system', 'content' => 'You are a helpful assistant.'),
                        array('role' => 'user', 'content' => $prompt),
                    ),
                    'max_tokens' => 1500
                )),
            ));

            if (is_wp_error($response)) {
                return array(
                    'success' => true,
                    'message' => $response
                );
            }

            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);

            if (!isset($result['choices'][0]['message']['content'])) {
                return array(
                    'success' => true,
                    'message' => 'error in to get content'
                );
            }

            $post_content = $result['choices'][0]['message']['content'];

            // Extract title, content, and FAQs from the response
            $matches = [];
            preg_match('/Title:(.*?)Content:(.*?)FAQs:(.*)/s', $post_content, $matches);

            if (count($matches) !== 4) {
                return array(
                    'success' => count($matches),
                    'matches' => $post_content,
                    'message' => 'Error in Pregmatch' // changed to full_content
                );
            }

            $title = preg_replace('/[-*#]/', '', trim($matches[1]));
            $content = preg_replace('/[-*#]/', '', trim($matches[2]));
            $faqs = preg_replace('/[-*#]/', '', trim($matches[3]));

            // Combine content and FAQs
            $full_content = $content . "\n\nFAQs:\n\n" . $faqs;
            // Extract keywords from content to use as tags
            function extractTags($content)
            {
                // Split content into words
                $words = preg_split('/\s+/', $content);

                // Filter out common words and short words
                $common_words = array('the', 'and', 'or', 'to', 'of', 'in', 'a', 'is', 'for', 'on', 'with');
                $min_word_length = 6;
                $filtered_words = array_filter($words, function ($word) use ($common_words, $min_word_length) {
                    return !in_array(strtolower($word), $common_words) && strlen($word) >= $min_word_length;
                });

                // Count word frequency
                $word_freq = array_count_values($filtered_words);

                // Sort words by frequency
                arsort($word_freq);

                // Return top 5 most frequent words as tags
                return array_slice(array_keys($word_freq), 0, 5);
            }
            $tags = extractTags($content);
            return array(
                'success' => true,
                'mix_content' => $post_content,
                'title' => $title,
                'content' => $full_content,
                'tags' => $tags
            );
            // Function to extract keywords from content

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function generate_featured_image($api_key, $prompt)
    {
        try {
            $response = wp_remote_post('https://api.openai.com/v1/images/generations', array(
                'timeout' => 50,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'body' => json_encode(array(
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024'
                )),
            ));

            if (is_wp_error($response)) {
                return array(
                    'msg' => 'response error',
                    'message' => $response
                );
            }

            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);

            if (!isset($result['data'][0]['url'])) {
                return array(
                   'msg' => 'result error',
                    'message' => $result
                );
            }

            return $result['data'][0]['url'];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function set_featured_image($image_url, $post_id, $title)
    {
        try {
            // Ensure required WordPress functions are available
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        
            // Get the upload directory info
            $upload_dir = wp_upload_dir();
        
            // Download the image data
            $response = wp_remote_get($image_url); // Using WordPress HTTP API
            if (is_wp_error($response)) {
                return array(
                    'message' => 'Error downloading image data',
                    'data' => $response->get_error_message()
                );
            }
        
            // Get the body of the response
            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                return array(
                    'message' => 'Empty response body',
                    'data' => ''
                );
            }
        
            // Determine file path
            $file_name = sanitize_file_name(str_replace(' ', '-', $title)) . '.png'; // Replacing spaces with hyphens
            $file = $upload_dir['path'] . '/' . $file_name;
        
            // Save the image to the server
            if (file_put_contents($file, $image_data) === false) {
                return array(
                    'message' => 'Failed to write image to: ' . $file,
                    'data' => $file
                );
            }
        
            // Load the image editor
            $image_editor = wp_get_image_editor($file);
            if (is_wp_error($image_editor)) {
                return array(
                    'message' => 'Error loading image editor',
                    'data' => $image_editor->get_error_message()
                );
            }
        
            // Resize the image
            $image_editor->resize(1200, 630, true);
        
            // Set the compression quality (0-100)
            $quality = 90; // Adjust this value if needed to ensure the file size is less than 200 KB
        
            // Save the resized and compressed image
            $saved = $image_editor->save($file, 'image/png', $quality);
            if (is_wp_error($saved)) {
                return array(
                    'message' => 'Error saving resized image',
                    'data' => $saved->get_error_message()
                );
            }
        
            // Check the file size
            if (filesize($file) > 200 * 1024) { // 200 KB
                // Retry with lower quality if the image is too large
                $quality = 75; // Adjust quality as needed
                $saved = $image_editor->save($file, 'image/png', $quality);
                if (is_wp_error($saved)) {
                    return array(
                        'message' => 'Error saving resized image with lower quality',
                        'data' => $saved->get_error_message()
                    );
                }
            }
        
            // Prepare the attachment array
            $attachment = array(
                'post_mime_type' => 'image/png',
                'post_title' => sanitize_file_name($title),
                'post_content' => '',
                'post_status' => 'inherit'
            );
        
            // Insert the attachment
            $attach_id = wp_insert_attachment($attachment, $file, $post_id);
        
            // Generate attachment metadata
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        
            // Update attachment metadata
            wp_update_attachment_metadata($attach_id, $attach_data);
        
            // Set the post thumbnail
            set_post_thumbnail($post_id, $attach_id);
        
            // Get the image URL
            $image_url = $upload_dir['url'] . '/' . $file_name;
        
            return array(
                'message' => 'Featured image set successfully',
                'filename' => $file,
                'image_url' => $image_url // Returning the URL of the image
            );
        } catch (\Throwable $th) {
            error_log('Error setting featured image: ' . $th->getMessage());
            throw $th;
        }
    }
    
    
    
    
}

// Instantiate and run the daily post creator
$daily_post_creator = new DailyPostCreator();
$check = $daily_post_creator->create_daily_post();

if (is_wp_error($check)) {
    echo "Error: " . $check->get_error_message();
} else {
    echo "<pre>";
    print_r($check);
}
