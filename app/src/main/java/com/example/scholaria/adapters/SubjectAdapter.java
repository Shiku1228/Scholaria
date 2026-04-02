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
import com.example.scholaria.models.Subject;
import com.google.android.material.button.MaterialButton;
import java.util.List;

public class SubjectAdapter extends RecyclerView.Adapter<SubjectAdapter.ViewHolder> {
    private List<Subject> subjects;

    public SubjectAdapter(List<Subject> subjects) {
        this.subjects = subjects;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_subject_card, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Subject subject = subjects.get(position);
        holder.tvName.setText(subject.getName());
        holder.tvCode.setText(subject.getCode());
        holder.tvCN.setText(subject.getClassNumber());

        View.OnClickListener viewSubjectListener = v -> {
            Intent intent = new Intent(v.getContext(), CourseDetailsActivity.class);
            intent.putExtra("COURSE_TITLE", subject.getName());
            intent.putExtra("COURSE_CODE", subject.getCode() + " | " + subject.getClassNumber());
            intent.putExtra("COURSE_PROGRESS", 0);
            v.getContext().startActivity(intent);
        };

        holder.itemView.setOnClickListener(viewSubjectListener);
        if (holder.btnViewSubject != null) {
            holder.btnViewSubject.setOnClickListener(viewSubjectListener);
        }
    }

    @Override
    public int getItemCount() { return subjects.size(); }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvName, tvCode, tvCN;
        MaterialButton btnViewSubject;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvName = itemView.findViewById(R.id.tvSubjectName);
            tvCode = itemView.findViewById(R.id.tvSubjectCode);
            tvCN = itemView.findViewById(R.id.tvClassNumber);
            btnViewSubject = itemView.findViewById(R.id.btnViewSubject);
        }
    }
}
