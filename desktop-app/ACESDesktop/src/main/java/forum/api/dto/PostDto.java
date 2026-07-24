package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

<<<<<<< HEAD
import java.util.List;

=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
/** JSON shape of a post/reply returned by the forum API. */
@JsonIgnoreProperties(ignoreUnknown = true)
public class PostDto {
    public long    post_id;
    public long    topic_id;
    public long    author_id;
    public Long    parent_post_id;   // nullable
    public String  content;
<<<<<<< HEAD
    public boolean is_flagged;
    public String  created_at;
    public String  author;

    // Only present when the caller is this post's own author — mirrors the
    // "🔒 Hidden from ..." badge on web (forum/show.blade.php).
    public List<String> excluded_usernames;
}
=======
    public String  created_at;
    public String  author;
}
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
