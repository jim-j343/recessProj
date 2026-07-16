package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;
import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class LecturerDashboardDto {
    @JsonProperty("quiz_count")
    public int quizCount;

    @JsonProperty("group_count")
    public int groupCount;

    @JsonProperty("topic_count")
    public int topicCount;

    @JsonProperty("quizzes")
    public List<QuizDto> quizzes;
}
