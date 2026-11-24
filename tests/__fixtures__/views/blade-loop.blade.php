<s:form:create in="contact">
    @foreach ($fields as $field)
        <div class="field">{!! $field['field'] !!}</div>
    @endforeach
</s:form:create>
