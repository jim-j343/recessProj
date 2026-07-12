package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class QuizDetailResponse {

    @JsonProperty("quiz")      public QuizDto       quiz;
    @JsonProperty("questions") public List<Question> questions;

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class Question {
        @JsonProperty("question_id") public long         questionId;
        @JsonProperty("content")     public String       content;
        @JsonProperty("marks")       public int          marks;
        @JsonProperty("answers")     public List<Answer> answers;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class Answer {
        @JsonProperty("answer_id") public long   answerId;
        @JsonProperty("content")   public String content;
    }
}