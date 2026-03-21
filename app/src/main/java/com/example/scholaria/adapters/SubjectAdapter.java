package com.example.scholaria.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.models.Subject;
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
    }

    @Override
    public int getItemCount() { return subjects.size(); }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvName, tvCode, tvCN;
        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvName = itemView.findViewById(R.id.tvSubjectName);
            tvCode = itemView.findViewById(R.id.tvSubjectCode);
            tvCN = itemView.findViewById(R.id.tvClassNumber);
        }
    }
}
