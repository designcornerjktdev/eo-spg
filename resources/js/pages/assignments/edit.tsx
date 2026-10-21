import AssignmentForm from './form';

export default function AssignmentEdit(props: Parameters<typeof AssignmentForm>[0]) {
    return <AssignmentForm {...props} />;
}
