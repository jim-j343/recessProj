package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

import java.util.List;

/** JSON shape of a post/reply returned by the forum API. */
@JsonIgnoreProperties(ignoreUnknown = true)
public class PostDto {
    public long    post_id;
    public long    topic_id;
    public long    author_id;
    public Long    parent_post_id;   // nullable
    public String  content;
    public String  created_at;
    public String  author;
    public List<String> excluded_usernames;
}
