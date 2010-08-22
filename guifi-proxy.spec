Name:           guifi-proxy
Version:        1.0
Release:        1%{?dist}
Summary:        Federated proxy for guifi.net

Group:          Applications/Internet
License:        GPL
URL:            http://guifi.net
Source0:        archive_name-%{version}
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildRequires:  
Requires:       

%description


%prep
%setup -q


%build
%configure
make %{?_smp_mflags}


%install
rm -rf $RPM_BUILD_ROOT
make install DESTDIR=$RPM_BUILD_ROOT


%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%doc



%changelog
