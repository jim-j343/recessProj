package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class AdminReportDto {
    public long id;
<<<<<<< HEAD
    @JsonProperty("topic_id") public Long topicId;
=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
    @JsonProperty("post_content") public String postContent;
    @JsonProperty("topic_title") public String topicTitle;
    public String author;
    @JsonProperty("reported_by") public String reportedBy;
    public String reason;
    public boolean reviewed;
    @JsonProperty("reviewed_by") public String reviewedBy;
    @JsonProperty("created_at") public String createdAt;
    @JsonProperty("created_at_human") public String createdAtHuman;
}
