import AssignmentForm from './form';

export default function AssignmentCreate(props: Parameters<typeof AssignmentForm>[0]) {
    return <AssignmentForm {...props} />;
}
