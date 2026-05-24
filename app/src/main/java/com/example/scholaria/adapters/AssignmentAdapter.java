package com.example.scholaria.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.models.Assignment;
import java.util.List;

public class AssignmentAdapter extends RecyclerView.Adapter<AssignmentAdapter.ViewHolder> {
    private List<Assignment> assignments;

    public AssignmentAdapter(List<Assignment> assignments) {
        this.assignments = assignments;
    }

    @NonNull
    @Override
     public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        // Updated to use item_assignment_card
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_assignment_card, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position){
        Assignment assignment = assignments.get(position);
        holder.tvAssignmentName.setText(assignment.getTitle() != null && !assignment.getTitle().trim().isEmpty()
                ? assignment.getTitle()
                : assignment.getSubject());

        String subject = assignment.getSubject() != null ? assignment.getSubject().trim() : "";
        String deadline = assignment.getDeadline() != null ? assignment.getDeadline().trim() : "";
        if (!subject.isEmpty() && !deadline.isEmpty()) {
            holder.tvAssignmentDue.setText(subject + " | " + deadline);
        } else if (!subject.isEmpty()) {
            holder.tvAssignmentDue.setText(subject);
        } else {
            holder.tvAssignmentDue.setText(deadline);
        }
    }

    @Override
    public int getItemCount() {
        return assignments.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvAssignmentName, tvAssignmentDue;
        public ViewHolder(@NonNull View itemView){
            super(itemView);
            // Updated to match IDs in item_assignment_card.xml
            tvAssignmentName = itemView.findViewById(R.id.tvAssignmentName);
            tvAssignmentDue = itemView.findViewById(R.id.tvAssignmentDue);
        }
    }
}
