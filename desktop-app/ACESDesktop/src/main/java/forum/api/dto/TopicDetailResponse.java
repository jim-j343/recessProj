package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

import java.util.List;

/** JSON body of GET /api/topics/{id}: the topic plus its posts. */
@JsonIgnoreProperties(ignoreUnknown = true)
public class TopicDetailResponse {
    public TopicDto      topic;
    public List<PostDto> posts;
}
