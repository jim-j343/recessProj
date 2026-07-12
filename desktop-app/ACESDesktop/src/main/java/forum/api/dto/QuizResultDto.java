package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class QuizResultDto {
    @JsonProperty("username")        public String  username;
    @JsonProperty("score")           public double  score;
    @JsonProperty("total")           public int     total;
    @JsonProperty("auto_submitted")  public boolean autoSubmitted;
    @JsonProperty("submitted_at")    public String  submittedAt;
}