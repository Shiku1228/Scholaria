package com.example.scholaria.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.models.Course;
import com.google.android.material.progressindicator.LinearProgressIndicator;
import java.util.List;

public class CourseAdapter extends RecyclerView.Adapter<CourseAdapter.ViewHolder> {
    private List<Course> courses;

    public CourseAdapter(List<Course> courses) {
        this.courses = courses;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_course_card, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Course course = courses.get(position);
        holder.tvTitle.setText(course.getTitle());
        holder.tvSubtitle.setText(course.getSubtitle());
        holder.tvSemester.setText(course.getSemester());
        holder.tvYear.setText(course.getYear());
        holder.tvProgressPercent.setText(course.getProgress() + "% Completed");
        holder.tvAssignmentsCount.setText(course.getAssignments());
        holder.progressIndicator.setProgress(course.getProgress());
    }

    @Override
    public int getItemCount() { return courses.size(); }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTitle, tvSubtitle, tvSemester, tvYear, tvProgressPercent, tvAssignmentsCount;
        LinearProgressIndicator progressIndicator;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTitle = itemView.findViewById(R.id.tvCourseTitle);
            tvSubtitle = itemView.findViewById(R.id.tvCourseSubtitle);
            tvSemester = itemView.findViewById(R.id.tvSemesterBadge);
            tvYear = itemView.findViewById(R.id.tvYearBadge);
            tvProgressPercent = itemView.findViewById(R.id.tvProgressPercent);
            tvAssignmentsCount = itemView.findViewById(R.id.tvAssignmentsCount);
            progressIndicator = itemView.findViewById(R.id.courseProgress);
        }
    }
}
