package com.example.scholaria.fragments;

import android.content.Intent;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.scholaria.R;
import com.example.scholaria.activities.TaskDetailActivity;
import com.example.scholaria.adapters.TaskAdapter;
import com.example.scholaria.models.TaskCategory;
import com.example.scholaria.models.TaskItem;
import com.example.scholaria.networks.ApiClient;
import com.example.scholaria.networks.StudentApiResponse;
import com.example.scholaria.networks.StudentTaskAssignmentDto;
import com.example.scholaria.networks.StudentTaskCourseDto;
import com.example.scholaria.networks.StudentTaskExamDto;
import com.example.scholaria.networks.StudentTaskHubDataDto;
import com.example.scholaria.networks.StudentTaskQuizDto;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.chip.Chip;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.textfield.MaterialAutoCompleteTextView;

import java.util.ArrayList;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class AssignmentFragment extends Fragment {

    private RecyclerView rvTasks;
    private TaskAdapter adapter;
    private MaterialAutoCompleteTextView actCourseFilter;
    private MaterialButton btnClearFilter;
    private Chip chipActiveCourse;
    private TabLayout taskTabLayout;
    private View cardEmptyState;
    private View rootView;

    private final List<TaskItem> allTasks = new ArrayList<>();
    private final List<TaskItem> displayedTasks = new ArrayList<>();
    private final List<String> courseOptions = new ArrayList<>();

    private TaskCategory selectedCategory = TaskCategory.ASSIGNMENT;
    private String selectedCourseFilter = null;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        rootView = inflater.inflate(R.layout.fragment_assignment, container, false);

        rvTasks = rootView.findViewById(R.id.rvTasks);
        actCourseFilter = rootView.findViewById(R.id.actCourseFilter);
        btnClearFilter = rootView.findViewById(R.id.btnClearFilter);
        chipActiveCourse = rootView.findViewById(R.id.chipActiveCourse);
        taskTabLayout = rootView.findViewById(R.id.taskTabLayout);
        cardEmptyState = rootView.findViewById(R.id.cardEmptyState);

        setupRecyclerView();
        setupCourseFilter();
        setupTabs();
        applyFilters();
        loadTasks();

        return rootView;
    }

    private void loadTasks() {
        if (getContext() == null) {
            return;
        }

        ApiClient.getInstance(requireContext())
                .getStudentTasksCall(null, null)
                .enqueue(new Callback<StudentApiResponse<StudentTaskHubDataDto>>() {
                    @Override
                    public void onResponse(@NonNull Call<StudentApiResponse<StudentTaskHubDataDto>> call, @NonNull Response<StudentApiResponse<StudentTaskHubDataDto>> response) {
                        if (!isAdded()) {
                            return;
                        }

                        if (!response.isSuccessful() || response.body() == null || response.body().getData() == null) {
                            return;
                        }

                        bindTasks(response.body().getData());
                    }

                    @Override
                    public void onFailure(@NonNull Call<StudentApiResponse<StudentTaskHubDataDto>> call, @NonNull Throwable t) {
                        // Leave the screen empty instead of showing fake data.
                    }
                });
    }

    private void bindTasks(StudentTaskHubDataDto data) {
        allTasks.clear();
        allTasks.addAll(mapAssignments(data.getAssignments()));
        allTasks.addAll(mapExams(data.getExams()));
        allTasks.addAll(mapQuizzes(data.getQuizzes()));

        courseOptions.clear();
        Set<String> uniqueCourses = new LinkedHashSet<>();
        for (TaskItem item : allTasks) {
            uniqueCourses.add(item.getCourseName());
        }
        courseOptions.addAll(uniqueCourses);
        setupCourseFilter();
        applyFilters();
    }

    private List<TaskItem> mapAssignments(List<StudentTaskAssignmentDto> data) {
        List<TaskItem> items = new ArrayList<>();
        int accentColor = ContextCompat.getColor(requireContext(), R.color.tasks_accent_sage);
        for (StudentTaskAssignmentDto dto : data) {
            boolean completed = dto.getSubmissionId() != null;
            items.add(new TaskItem(
                    "asg-" + dto.getAssignmentId(),
                    TaskCategory.ASSIGNMENT,
                    safe(dto.getCourseTitle(), "Course"),
                    safe(dto.getTitle(), "Assignment"),
                    formatDueLine(dto.getSubmittedAt(), dto.isOverdue()),
                    buildMeta(dto.getStatus(), dto.getScore(), dto.getSubmittedAt()),
                    completed ? "View" : "Submit",
                    statusLabel(dto.getStatus(), completed, dto.isOverdue()),
                    dto.getScore() != null ? String.format(Locale.US, "%s", dto.getScore()) : "",
                    completed,
                    dto.isOverdue(),
                    accentColor,
                    R.drawable.ic_edit_square
            ));
        }
        return items;
    }

    private List<TaskItem> mapExams(List<StudentTaskExamDto> data) {
        List<TaskItem> items = new ArrayList<>();
        int accentColor = ContextCompat.getColor(requireContext(), R.color.dash_teal);
        for (StudentTaskExamDto dto : data) {
            boolean completed = dto.getAttemptId() != null;
            items.add(new TaskItem(
                    "exm-" + dto.getId(),
                    TaskCategory.EXAM,
                    safe(dto.getCourseTitle(), "Course"),
                    safe(dto.getTitle(), "Exam"),
                    formatDueLine(dto.getSubmittedAt(), dto.isOverdue()),
                    buildMeta(dto.getStatus(), dto.getScore(), dto.getSubmittedAt()),
                    completed ? "View" : "Start",
                    statusLabel(dto.getStatus(), completed, dto.isOverdue()),
                    dto.getScore() != null ? String.format(Locale.US, "%s", dto.getScore()) : "",
                    completed,
                    dto.isOverdue(),
                    accentColor,
                    R.drawable.ic_security_lock
            ));
        }
        return items;
    }

    private List<TaskItem> mapQuizzes(List<StudentTaskQuizDto> data) {
        List<TaskItem> items = new ArrayList<>();
        int accentColor = ContextCompat.getColor(requireContext(), R.color.accent_teal);
        for (StudentTaskQuizDto dto : data) {
            boolean completed = dto.getAttemptId() != null;
            items.add(new TaskItem(
                    "quiz-" + dto.getId(),
                    TaskCategory.QUIZ,
                    safe(dto.getCourseTitle(), "Course"),
                    safe(dto.getTitle(), "Quiz"),
                    formatDueLine(dto.getSubmittedAt(), dto.isOverdue()),
                    buildMeta(dto.getStatus(), dto.getScore(), dto.getSubmittedAt()),
                    completed ? "View" : "Start",
                    statusLabel(dto.getStatus(), completed, dto.isOverdue()),
                    dto.getScore() != null ? String.format(Locale.US, "%s", dto.getScore()) : "",
                    completed,
                    dto.isOverdue(),
                    accentColor,
                    R.drawable.ic_lesson
            ));
        }
        return items;
    }

    private void setupRecyclerView() {
        adapter = new TaskAdapter(displayedTasks, this::openTaskDetails);
        rvTasks.setLayoutManager(new LinearLayoutManager(getContext()));
        rvTasks.setHasFixedSize(false);
        rvTasks.setAdapter(adapter);
    }

    private void setupCourseFilter() {
        List<String> dropdownItems = new ArrayList<>();
        dropdownItems.add("All courses");
        dropdownItems.addAll(courseOptions);

        ArrayAdapter<String> filterAdapter = new ArrayAdapter<>(requireContext(), android.R.layout.simple_list_item_1, dropdownItems);
        actCourseFilter.setAdapter(filterAdapter);
        actCourseFilter.setText(selectedCourseFilter == null ? "All courses" : selectedCourseFilter, false);
        actCourseFilter.setOnItemClickListener((parent, view, position, id) -> {
            String value = (String) parent.getItemAtPosition(position);
            selectedCourseFilter = "All courses".equals(value) ? null : value;
            syncCourseFilterUi();
            applyFilters();
        });

        btnClearFilter.setOnClickListener(v -> clearTaskFilter());
        chipActiveCourse.setOnCloseIconClickListener(v -> clearTaskFilter());
    }

    private void setupTabs() {
        taskTabLayout.removeAllTabs();
        taskTabLayout.addTab(taskTabLayout.newTab().setText("Assignments"));
        taskTabLayout.addTab(taskTabLayout.newTab().setText("Exams"));
        taskTabLayout.addTab(taskTabLayout.newTab().setText("Quizzes"));
        taskTabLayout.addOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                if (tab.getPosition() == 0) selectedCategory = TaskCategory.ASSIGNMENT;
                else if (tab.getPosition() == 1) selectedCategory = TaskCategory.EXAM;
                else selectedCategory = TaskCategory.QUIZ;
                applyFilters();
            }

            @Override public void onTabUnselected(TabLayout.Tab tab) {}

            @Override public void onTabReselected(TabLayout.Tab tab) { applyFilters(); }
        });

        TabLayout.Tab initialTab = taskTabLayout.getTabAt(0);
        if (initialTab != null) initialTab.select();
    }

    private void clearTaskFilter() {
        selectedCourseFilter = null;
        actCourseFilter.setText("All courses", false);
        syncCourseFilterUi();
        applyFilters();
    }

    private void syncCourseFilterUi() {
        boolean hasFilter = selectedCourseFilter != null && !selectedCourseFilter.isEmpty();
        btnClearFilter.setVisibility(hasFilter ? View.VISIBLE : View.GONE);
        chipActiveCourse.setVisibility(hasFilter ? View.VISIBLE : View.GONE);
        chipActiveCourse.setText(hasFilter ? selectedCourseFilter : "All courses");
        if (hasFilter) actCourseFilter.setText(selectedCourseFilter, false);
    }

    private void applyFilters() {
        displayedTasks.clear();
        for (TaskItem item : allTasks) {
            if (item.getCategory() != selectedCategory) continue;
            if (selectedCourseFilter != null && !selectedCourseFilter.equals(item.getCourseName())) continue;
            displayedTasks.add(item);
        }
        if (adapter != null) adapter.notifyDataSetChanged();
        updateSummaryCards();
        updateEmptyState();
    }

    private void updateSummaryCards() {
        int asgCount = 0, exmCount = 0, quizCount = 0;
        for (TaskItem item : allTasks) {
            if (selectedCourseFilter != null && !selectedCourseFilter.equals(item.getCourseName())) continue;
            if (item.getCategory() == TaskCategory.ASSIGNMENT) asgCount++;
            else if (item.getCategory() == TaskCategory.EXAM) exmCount++;
            else if (item.getCategory() == TaskCategory.QUIZ) quizCount++;
        }
        TextView tvAsg = rootView.findViewById(R.id.tvAssignmentsSummaryCount);
        TextView tvExm = rootView.findViewById(R.id.tvExamsSummaryCount);
        TextView tvQuiz = rootView.findViewById(R.id.tvQuizzesSummaryCount);
        if (tvAsg != null) tvAsg.setText(String.valueOf(asgCount));
        if (tvExm != null) tvExm.setText(String.valueOf(exmCount));
        if (tvQuiz != null) tvQuiz.setText(String.valueOf(quizCount));
    }

    private void updateEmptyState() {
        boolean isEmpty = displayedTasks.isEmpty();
        if (rvTasks != null) rvTasks.setVisibility(isEmpty ? View.GONE : View.VISIBLE);
        if (cardEmptyState != null) {
            cardEmptyState.setVisibility(isEmpty ? View.VISIBLE : View.GONE);
            if (isEmpty) {
                TextView title = cardEmptyState.findViewById(R.id.tvEmptyStateTitle);
                if (title != null) title.setText(selectedCategory.getDisplayName() + " not found");
            }
        }
    }

    private void openTaskDetails(TaskItem item) {
        Intent intent = new Intent(requireContext(), TaskDetailActivity.class);
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_ID, item.getId());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_CATEGORY, item.getCategory().name());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_TITLE, item.getTitle());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_COURSE, item.getCourseName());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_DETAIL, item.getDetailLine());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_META, item.getMetaLine());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_ACTION, item.getActionLabel());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_STATUS, item.getStatusLabel());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_SCORE, item.getScoreText());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_COMPLETED, item.isCompleted());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_OVERDUE, item.isOverdue());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_ACCENT_COLOR, item.getAccentColor());
        intent.putExtra(TaskDetailActivity.EXTRA_TASK_ICON_RES, item.getIconRes());
        startActivity(intent);
    }

    private String safe(String value, String fallback) {
        return value == null || value.trim().isEmpty() ? fallback : value;
    }

    private String formatDueLine(String submittedAt, boolean overdue) {
        if (submittedAt != null && !submittedAt.trim().isEmpty()) {
            return "Submitted: " + submittedAt;
        }
        return overdue ? "Due date passed" : "Pending";
    }

    private String buildMeta(String status, Integer score, String submittedAt) {
        StringBuilder builder = new StringBuilder();
        if (status != null && !status.isEmpty()) {
            builder.append(status);
        }
        if (score != null) {
            if (builder.length() > 0) builder.append(" | ");
            builder.append("Score: ").append(score);
        }
        if (submittedAt != null && !submittedAt.isEmpty()) {
            if (builder.length() > 0) builder.append(" | ");
            builder.append(submittedAt);
        }
        return builder.length() > 0 ? builder.toString() : "No status";
    }

    private String statusLabel(String status, boolean completed, boolean overdue) {
        if (completed) return "Done";
        if (overdue) return "Overdue";
        return status != null && !status.isEmpty() ? status.substring(0, 1).toUpperCase() + status.substring(1) : "Pending";
    }
}
