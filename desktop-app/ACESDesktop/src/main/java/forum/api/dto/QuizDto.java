package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class QuizDto {
    @JsonProperty("quiz_id")              public long    quizId;
    @JsonProperty("title")                public String  title;
    @JsonProperty("group_id")             public long    groupId;
    @JsonProperty("course_name")          public String  courseName;
    @JsonProperty("eligible_group_count") public int     eligibleGroupCount;
    @JsonProperty("total_marks")          public int     totalMarks;
    @JsonProperty("start_time")           public String  startTime;
    @JsonProperty("duration_minutes")     public int     durationMinutes;
    @JsonProperty("is_published")         public boolean isPublished;
    @JsonProperty("target_category")      public String  targetCategory;
    @JsonProperty("my_result")            public QuizResultDto myResult;
}