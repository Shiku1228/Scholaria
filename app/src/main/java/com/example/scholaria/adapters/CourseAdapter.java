package com.example.scholaria.adapters;

import android.content.Intent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.activities.CourseDetailsActivity;
import com.example.scholaria.models.Course;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.progressindicator.LinearProgressIndicator;
import java.util.List;

public class CourseAdapter extends RecyclerView.Adapter<CourseAdapter.ViewHolder> {
    public static final int VIEW_TYPE_LARGE = 0;
    public static final int VIEW_TYPE_SMALL = 1;
    public static final int VIEW_TYPE_LIST = 2;

    private final List<Course> courses;
    private int currentViewType = VIEW_TYPE_LARGE;

    public CourseAdapter(List<Course> courses) {
        this.courses = courses;
    }

    public void setViewType(int viewType) {
        this.currentViewType = viewType;
        notifyDataSetChanged();
    }

    @Override
    public int getItemViewType(int position) {
        return currentViewType;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        int layoutRes;
        if (viewType == VIEW_TYPE_SMALL) {
            layoutRes = R.layout.item_course_small;
        } else if (viewType == VIEW_TYPE_LIST) {
            layoutRes = R.layout.item_course_list;
        } else {
            layoutRes = R.layout.item_course_card;
        }
        View view = LayoutInflater.from(parent.getContext()).inflate(layoutRes, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Course course = courses.get(position);
        if (holder.tvTitle != null) holder.tvTitle.setText(course.getCourseName());
        if (holder.tvSubtitle != null) holder.tvSubtitle.setText(course.getTeacherName() + " | " + course.getCourseNumber());
        if (holder.tvSemester != null) holder.tvSemester.setText(course.getSemester());
        if (holder.tvYear != null) holder.tvYear.setText(course.getSchoolYear());
        if (holder.tvProgressPercent != null) {
            if (currentViewType == VIEW_TYPE_LIST) {
                holder.tvProgressPercent.setText(course.getProgress() + "%");
            } else {
                holder.tvProgressPercent.setText(course.getProgress() + "% Completed");
            }
        }
        if (holder.tvAssignmentsCount != null) {
            holder.tvAssignmentsCount.setText(course.getAssignmentsSubmitted() + "/" + course.getAssignmentsTotal() + " assignments");
        }
        if (holder.progressIndicator != null) holder.progressIndicator.setProgress(course.getProgress());

        View.OnClickListener openCourseListener = v -> {
            Intent intent = new Intent(v.getContext(), CourseDetailsActivity.class);
            intent.putExtra(CourseDetailsActivity.EXTRA_COURSE_ID, course.getCourseId());
            intent.putExtra(CourseDetailsActivity.EXTRA_COURSE_NAME, course.getCourseName());
            intent.putExtra(CourseDetailsActivity.EXTRA_COURSE_NUMBER, course.getCourseNumber());
            intent.putExtra(CourseDetailsActivity.EXTRA_TEACHER_NAME, course.getTeacherName());
            intent.putExtra(CourseDetailsActivity.EXTRA_COVER_RES, course.getCoverImageRes());
            intent.putExtra(CourseDetailsActivity.EXTRA_PROGRESS, course.getProgress());
            intent.putExtra(CourseDetailsActivity.EXTRA_ASSIGNMENTS_TOTAL, course.getAssignmentsTotal());
            intent.putExtra(CourseDetailsActivity.EXTRA_ASSIGNMENTS_SUBMITTED, course.getAssignmentsSubmitted());
            intent.putExtra(CourseDetailsActivity.EXTRA_SEMESTER, course.getSemester());
            intent.putExtra(CourseDetailsActivity.EXTRA_SCHOOL_YEAR, course.getSchoolYear());
            v.getContext().startActivity(intent);
        };

        holder.itemView.setOnClickListener(openCourseListener);
        if (holder.btnOpenCourse != null) {
            holder.btnOpenCourse.setOnClickListener(openCourseListener);
        }
        if (holder.btnViewAssignments != null) {
            holder.btnViewAssignments.setOnClickListener(v -> {
                Intent intent = new Intent(v.getContext(), CourseDetailsActivity.class);
                intent.putExtra(CourseDetailsActivity.EXTRA_COURSE_ID, course.getCourseId());
                intent.putExtra(CourseDetailsActivity.EXTRA_COURSE_NAME, course.getCourseName());
                intent.putExtra(CourseDetailsActivity.EXTRA_COURSE_NUMBER, course.getCourseNumber());
                intent.putExtra(CourseDetailsActivity.EXTRA_TEACHER_NAME, course.getTeacherName());
                intent.putExtra(CourseDetailsActivity.EXTRA_COVER_RES, course.getCoverImageRes());
                intent.putExtra(CourseDetailsActivity.EXTRA_PROGRESS, course.getProgress());
                intent.putExtra(CourseDetailsActivity.EXTRA_ASSIGNMENTS_TOTAL, course.getAssignmentsTotal());
                intent.putExtra(CourseDetailsActivity.EXTRA_ASSIGNMENTS_SUBMITTED, course.getAssignmentsSubmitted());
                intent.putExtra(CourseDetailsActivity.EXTRA_SEMESTER, course.getSemester());
                intent.putExtra(CourseDetailsActivity.EXTRA_SCHOOL_YEAR, course.getSchoolYear());
                intent.putExtra(CourseDetailsActivity.EXTRA_INITIAL_TAB, 1);
                v.getContext().startActivity(intent);
            });
        }
    }

    @Override
    public int getItemCount() {
        return courses.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTitle, tvSubtitle, tvSemester, tvYear, tvProgressPercent, tvAssignmentsCount;
        LinearProgressIndicator progressIndicator;
        MaterialButton btnOpenCourse;
        MaterialButton btnViewAssignments;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTitle = itemView.findViewById(R.id.tvCourseTitle);
            tvSubtitle = itemView.findViewById(R.id.tvCourseSubtitle);
            tvSemester = itemView.findViewById(R.id.tvSemesterBadge);
            tvYear = itemView.findViewById(R.id.tvYearBadge);
            tvProgressPercent = itemView.findViewById(R.id.tvProgressPercent);
            tvAssignmentsCount = itemView.findViewById(R.id.tvAssignmentsCount);
            progressIndicator = itemView.findViewById(R.id.courseProgress);
            btnOpenCourse = itemView.findViewById(R.id.btnOpenCourse);
            btnViewAssignments = itemView.findViewById(R.id.btnViewAssignments);
        }
    }
}
