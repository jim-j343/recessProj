package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

/** JSON shape of a topic returned by the forum API. */
@JsonIgnoreProperties(ignoreUnknown = true)
public class TopicDto {
    public long    topic_id;
    public long    group_id;
    public long    creator_id;
    public String  title;
    public String  category;
    public String  created_at;
    public String  author;
    public int     replies;
<<<<<<< HEAD
    public int     posts_count;
=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
}
