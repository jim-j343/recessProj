package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
<<<<<<< HEAD
import com.fasterxml.jackson.annotation.JsonProperty;

import java.util.List;

/** JSON body of GET /api/topics/{id}: the topic, its posts, and the group roster. */
=======

import java.util.List;

/** JSON body of GET /api/topics/{id}: the topic plus its posts. */
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
@JsonIgnoreProperties(ignoreUnknown = true)
public class TopicDetailResponse {
    public TopicDto      topic;
    public List<PostDto> posts;
<<<<<<< HEAD

    // Other active members of this topic's group — populates the
    // "exclude from this reply" picker.
    @JsonProperty("group_members") public List<MemberDto> groupMembers;
}
=======
}
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
