package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.util.List;

/** JSON body of GET /api/topics/{id}: the topic, its posts, and the group roster. */
@JsonIgnoreProperties(ignoreUnknown = true)
public class TopicDetailResponse {
    public TopicDto      topic;
    public List<PostDto> posts;

    // Other active members of this topic's group — populates the
    // "exclude from this reply" picker.
    @JsonProperty("group_members") public List<MemberDto> groupMembers;
}