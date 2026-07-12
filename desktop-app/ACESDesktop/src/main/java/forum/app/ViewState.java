package forum.app;

import forum.api.dto.GroupDto;
import forum.api.dto.QuizDetailResponse;
import forum.api.dto.QuizDto;
import forum.models.Topic;

import java.util.concurrent.atomic.AtomicReference;

/** Lightweight holder for data passed between screens. Thread-safe using AtomicReference. */
public final class ViewState {

    private static final AtomicReference<Topic>    selectedTopic = new AtomicReference<>();
    private static final AtomicReference<GroupDto> selectedGroup = new AtomicReference<>();
    private static final AtomicReference<QuizDto>  selectedQuiz = new AtomicReference<>();
    private static final AtomicReference<QuizDetailResponse> selectedQuizDetail = new AtomicReference<>();
    private static final AtomicReference<String>   callerScreen = new AtomicReference<>();

    private ViewState() {}

    public static Topic getSelectedTopic()      { return selectedTopic.get(); }
    public static void  setSelectedTopic(Topic t) { selectedTopic.set(t); }

    public static GroupDto getSelectedGroup()           { return selectedGroup.get(); }
    public static void     setSelectedGroup(GroupDto g) { selectedGroup.set(g); }

    public static QuizDto getSelectedQuiz()           { return selectedQuiz.get(); }
    public static void    setSelectedQuiz(QuizDto q)  { selectedQuiz.set(q); }

    public static QuizDetailResponse getSelectedQuizDetail()                    { return selectedQuizDetail.get(); }
    public static void               setSelectedQuizDetail(QuizDetailResponse r){ selectedQuizDetail.set(r); }

    public static String getCallerScreen()            { return callerScreen.get(); }
    public static void   setCallerScreen(String s)    { callerScreen.set(s); }
}
