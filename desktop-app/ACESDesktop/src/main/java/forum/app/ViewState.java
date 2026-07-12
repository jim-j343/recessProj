package forum.app;

import forum.api.dto.GroupDto;
import forum.api.dto.QuizDetailResponse;
import forum.api.dto.QuizDto;
import forum.models.Topic;

/** Lightweight holder for data passed between screens. */
public final class ViewState {

    private static Topic    selectedTopic;
    private static GroupDto selectedGroup;
    private static QuizDto  selectedQuiz;
    private static QuizDetailResponse selectedQuizDetail;
    private static String   callerScreen; // which screen to go back to

    private ViewState() {}

    public static Topic    getSelectedTopic()      { return selectedTopic; }
    public static void     setSelectedTopic(Topic t) { selectedTopic = t; }

    public static GroupDto getSelectedGroup()           { return selectedGroup; }
    public static void     setSelectedGroup(GroupDto g) { selectedGroup = g; }

    public static QuizDto  getSelectedQuiz()           { return selectedQuiz; }
    public static void     setSelectedQuiz(QuizDto q)  { selectedQuiz = q; }

    public static QuizDetailResponse getSelectedQuizDetail()                    { return selectedQuizDetail; }
    public static void               setSelectedQuizDetail(QuizDetailResponse r){ selectedQuizDetail = r; }

    public static String getCallerScreen()            { return callerScreen; }
    public static void   setCallerScreen(String s)    { callerScreen = s; }
}